<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_member_is_redirected_to_stocks(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($user)->get('/')->assertRedirect('/stocks');
    }

    public function test_admin_is_redirected_to_admin_users(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/')->assertRedirect('/admin/users');
    }
}
