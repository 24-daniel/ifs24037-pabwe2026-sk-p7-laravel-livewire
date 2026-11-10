<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class StockListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function lw()
    {
        return Livewire::actingAs($this->user)->test(StockList::class);
    }

    private function xlsx(array $rows): UploadedFile
    {
        $s = new Spreadsheet;
        $s->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'x');
        (new Xlsx($s))->save($path);

        return UploadedFile::fake()->createWithContent('stok.xlsx', file_get_contents($path));
    }

    public function test_filters_and_pagination_reset(): void
    {
        Stock::factory()->create(['user_id' => $this->user->id, 'name' => 'Meja Belajar', 'sku' => 'MB-1']);
        $this->lw()->assertSee('Meja Belajar')
            ->set('search', 'MB-1')->assertSee('Meja Belajar')
            ->set('statusFilter', 'x')->assertDontSee('Meja Belajar')
            ->call('resetFilters')->assertSet('search', '')->assertSee('Meja Belajar');
    }

    public function test_create_and_validation(): void
    {
        $this->lw()->call('openCreate')->assertSet('showForm', true)
            ->set('name', '')->call('save')->assertHasErrors('name')
            ->set('name', 'Pensil')->set('quantity', '10')->set('price', '2500')->set('sku', 'PN-1')->set('description', 'abc')
            ->call('save')->assertHasNoErrors()->assertSet('showForm', false)->assertDispatched('notify');
        $this->assertDatabaseHas('stocks', ['name' => 'Pensil', 'sku' => 'PN-1', 'user_id' => $this->user->id]);

        $this->lw()->call('openCreate')->set('name', 'Polos')->call('save');
        $this->assertDatabaseHas('stocks', ['name' => 'Polos', 'sku' => null, 'description' => null, 'price' => null]);
    }

    public function test_admin_cannot_create(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(StockList::class)
            ->set('name', 'X')->call('save')->assertForbidden();
        Livewire::actingAs(User::factory()->admin()->create())->test(StockList::class)->call('saveAi')->assertForbidden();
    }

    public function test_edit_adjust_delete(): void
    {
        $s = Stock::factory()->create(['user_id' => $this->user->id, 'quantity' => 1, 'price' => 1500, 'sku' => null, 'description' => null]);
        $n = Stock::factory()->create(['user_id' => $this->user->id, 'price' => null]);

        $c = $this->lw()->call('openEdit', $s->id)->assertSet('editingId', $s->id)->assertSet('price', '1500')
            ->set('name', 'Diedit')->call('save')->assertDispatched('notify');
        $this->assertSame('Diedit', $s->fresh()->name);

        $this->lw()->call('openEdit', $n->id)->assertSet('price', '');

        $c->call('adjust', $s->id, 5)->call('adjust', $s->id, -1)->call('adjust', $s->id, -1);
        $this->assertSame(0, $s->fresh()->quantity);

        $c->call('closeForm')->call('delete', $s->id)->assertDispatched('notify');
        $this->assertModelMissing($s);
    }

    public function test_cannot_touch_others_stock(): void
    {
        $o = Stock::factory()->create();
        $this->lw()->call('openEdit', $o->id)->assertForbidden();
        $this->lw()->call('delete', $o->id)->assertForbidden();
    }

    public function test_export(): void
    {
        Stock::factory()->count(2)->create(['user_id' => $this->user->id]);
        $this->lw()->call('export')->assertFileDownloaded();
    }

    public function test_import_flow(): void
    {
        $c = $this->lw()->call('openImport')->assertSet('showImport', true);
        $c->call('import')->assertHasErrors('importFile');

        $c->set('importFile', UploadedFile::fake()->createWithContent('bad.xlsx', 'bukan excel'))->call('import')->assertHasErrors('importFile');
        $c->set('importFile', $this->xlsx([['Foo'], ['x']]))->call('import')->assertHasErrors('importFile');

        $c->set('importFile', $this->xlsx([
            ['Nama', 'SKU', 'Qty', 'Satuan', 'Harga', 'Min Stock', 'Deskripsi'],
            ['Buku', 'B-1', 5, 'box', 1000, 2, 'desc'],
            ['Pena', '', 'abc', '', 'abc', 'abc', ''],
            ['', 'kosong', 1, 'x', 1, 1, ''],
        ]))->call('import')->assertHasNoErrors()->assertDispatched('notify')->assertSet('showImport', false);

        $this->assertDatabaseHas('stocks', ['name' => 'Buku', 'quantity' => 5, 'unit' => 'box']);
        $this->assertDatabaseHas('stocks', ['name' => 'Pena', 'quantity' => 0, 'unit' => 'pcs', 'min_stock' => 5]);
        $this->assertDatabaseCount('stocks', 2);

        $c->call('closeImport')->assertSet('showImport', false);
    }

    public function test_ai_flow(): void
    {
        $this->app->instance(AiClientInterface::class, new class implements AiClientInterface
        {
            public function generate(string $topic, int $count = 5): array
            {
                return match ($topic) {
                    'runtime' => throw new RuntimeException('gagal runtime'),
                    'error' => throw new \LogicException('boom'),
                    default => [
                        GeneratedStockItem::fromArray(['name' => 'A', 'quantity' => 3]),
                        GeneratedStockItem::fromArray(['name' => 'B']),
                    ],
                };
            }
        });

        $c = $this->lw()->call('openAi')->assertSet('showAi', true)
            ->call('generateAi')->assertHasErrors('aiTopic');

        $c->set('aiTopic', 'runtime')->call('generateAi')->assertSet('aiError', 'gagal runtime');
        $c->set('aiTopic', 'error')->call('generateAi')->assertSet('aiError', 'Terjadi kesalahan saat menghubungi AI. Coba lagi.');
        $c->set('aiTopic', 'alat tulis')->call('generateAi')->assertSet('aiSelected', ['0', '1']);

        $c->set('aiSelected', ['0', '9'])->call('saveAi')->assertDispatched('notify')->assertSet('showAi', false);
        $this->assertDatabaseCount('stocks', 1);
        $this->assertDatabaseHas('stocks', ['name' => 'A', 'quantity' => 3]);

        $c->call('openAi')->call('closeAi')->assertSet('showAi', false);
    }
}
