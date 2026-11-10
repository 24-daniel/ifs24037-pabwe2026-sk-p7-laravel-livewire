<?php

namespace Tests\Feature;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_list_search(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Zed Unik', 'username' => 'zedunik']);

        Livewire::actingAs($admin)->test(UserList::class)
            ->assertSee('Zed Unik')
            ->set('search', 'zedunik')->assertSee('Zed Unik')
            ->set('search', 'tidakada123')->assertDontSee('Zed Unik');
    }

    public function test_admin_user_stock_list(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Kursi Kayu', 'quantity' => 0, 'status' => 'out_of_stock']);

        Livewire::actingAs($admin)->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Kursi Kayu')
            ->set('search', 'Kursi')->assertSee('Kursi Kayu')
            ->set('statusFilter', 'available')->assertDontSee('Kursi Kayu')
            ->set('statusFilter', 'out_of_stock')->assertSee('Kursi Kayu');
    }
}
