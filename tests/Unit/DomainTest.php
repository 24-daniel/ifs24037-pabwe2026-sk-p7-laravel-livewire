<?php

namespace Tests\Unit;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_status_from_quantity_label_and_badge(): void
    {
        $this->assertSame(StockStatus::OutOfStock, StockStatus::fromQuantity(0, 5));
        $this->assertSame(StockStatus::LowStock, StockStatus::fromQuantity(5, 5));
        $this->assertSame(StockStatus::Available, StockStatus::fromQuantity(6, 5));

        $expected = [
            'available' => ['Tersedia', 'bg-green-100 text-green-800'],
            'low_stock' => ['Stok Menipis', 'bg-yellow-100 text-yellow-800'],
            'out_of_stock' => ['Habis', 'bg-red-100 text-red-800'],
        ];
        foreach (StockStatus::cases() as $case) {
            $this->assertSame($expected[$case->value][0], $case->label());
            $this->assertSame($expected[$case->value][1], $case->badgeClass());
        }
    }

    public function test_user_helpers_and_relations(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Budi Santoso Wijaya']);
        $single = User::factory()->create(['name' => 'Cher']);
        $member = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($member->isAdmin());
        $this->assertSame('BW', $admin->initials());
        $this->assertSame('C', $single->initials());

        $stock = Stock::factory()->create(['user_id' => $member->id]);
        $this->assertTrue($member->stocks->first()->is($stock));
        $this->assertTrue($stock->user->is($member));
    }

    public function test_stock_policy(): void
    {
        $policy = new StockPolicy;
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($policy->viewAny($owner));
        $this->assertFalse($policy->viewAny($admin));
        $this->assertTrue($policy->create($owner));
        $this->assertFalse($policy->create($admin));
        $this->assertTrue($policy->view($owner, $stock));
        $this->assertFalse($policy->view($other, $stock));
        $this->assertTrue($policy->update($owner, $stock));
        $this->assertFalse($policy->update($other, $stock));
        $this->assertTrue($policy->delete($owner, $stock));
        $this->assertFalse($policy->delete($other, $stock));
    }

    public function test_generated_stock_item_defaults_and_to_array(): void
    {
        $item = GeneratedStockItem::fromArray([]);
        $this->assertSame('Barang tanpa nama', $item->name);
        $this->assertNull($item->sku);
        $this->assertNull($item->description);
        $this->assertSame(0, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertNull($item->price);
        $this->assertSame(5, $item->minStock);

        $full = GeneratedStockItem::fromArray([
            'name' => ' Pulpen ', 'sku' => 'P-1', 'description' => 'Desc', 'quantity' => '-3',
            'unit' => 'box', 'price' => '1500.5', 'minStock' => 2,
        ]);
        $this->assertSame([
            'name' => 'Pulpen', 'sku' => 'P-1', 'description' => 'Desc', 'quantity' => 0,
            'unit' => 'box', 'price' => 1500.5, 'min_stock' => 2,
        ], $full->toArray());

        $this->assertSame(7, GeneratedStockItem::fromArray(['min_stock' => 7])->minStock);
        $this->assertSame(5, GeneratedStockItem::fromArray(['min_stock' => 'x'])->minStock);
    }
}
