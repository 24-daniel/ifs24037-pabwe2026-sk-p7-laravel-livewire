<?php

namespace Tests\Feature;

use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->stock = Stock::factory()->create(['user_id' => $this->user->id, 'quantity' => 1, 'price' => 2000]);
        Storage::fake('public');
    }

    private function lw()
    {
        return Livewire::actingAs($this->user)->test(StockDetail::class, ['stock' => $this->stock]);
    }

    public function test_view_forbidden_for_other_user(): void
    {
        Livewire::actingAs(User::factory()->create())->test(StockDetail::class, ['stock' => $this->stock])->assertForbidden();
    }

    public function test_edit_cycle(): void
    {
        $this->lw()->assertSet('price', '2000')
            ->call('startEdit')->assertSet('editing', true)
            ->call('cancelEdit')->assertSet('editing', false)
            ->call('startEdit')->set('name', '')->call('save')->assertHasErrors('name')
            ->set('name', 'Baru')->set('sku', '')->set('description', '')->set('price', '')->set('quantity', '3')
            ->call('save')->assertSet('editing', false)->assertDispatched('notify');

        $fresh = $this->stock->fresh();
        $this->assertSame('Baru', $fresh->name);
        $this->assertNull($fresh->sku);
        $this->assertNull($fresh->price);
    }

    public function test_price_null_on_mount(): void
    {
        $this->stock->update(['price' => null]);
        $this->lw()->assertSet('price', '');
    }

    public function test_adjust_and_delete(): void
    {
        $this->lw()->call('adjust', 1)->assertSet('quantity', '2')->call('adjust', -1)->call('adjust', -1)->call('adjust', -1)->assertSet('quantity', '0');
        $this->lw()->call('delete')->assertRedirect(route('stocks.index'));
        $this->assertModelMissing($this->stock);
    }

    public function test_cover_upload_replace_remove(): void
    {
        $c = $this->lw()->set('cover', UploadedFile::fake()->image('a.jpg'))->assertDispatched('notify');
        $first = $this->stock->fresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $c->set('cover', UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);

        $c->call('removeCover')->assertDispatched('notify');
        $this->assertNull($this->stock->fresh()->cover_image_path);
        $c->call('removeCover');
    }

    public function test_cover_invalid_and_null(): void
    {
        $this->lw()->set('cover', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))->assertHasErrors('cover');
        $this->lw()->call('updatedCover')->assertNotDispatched('notify');
    }
}
