<?php

namespace Tests\Feature;

use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_email_and_username(): void
    {
        $user = User::factory()->create(['username' => 'andi', 'email' => 'andi@example.com']);

        Livewire::test(Login::class)->set('login', 'andi@example.com')->set('password', 'password')
            ->set('remember', true)->call('submit')->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        Livewire::test(Login::class)->set('login', 'andi')->set('password', 'password')
            ->call('submit')->assertRedirect('/');
    }

    public function test_login_failure_and_validation(): void
    {
        User::factory()->create(['username' => 'andi']);

        Livewire::test(Login::class)->set('login', 'andi')->set('password', 'salah')
            ->call('submit')->assertHasErrors(['login']);
        Livewire::test(Login::class)->call('submit')->assertHasErrors(['login' => 'required', 'password' => 'required']);
    }

    public function test_register(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi')->set('username', 'budi_1')->set('email', 'budi@example.com')
            ->set('password', 'secret123')->set('password_confirmation', 'secret123')
            ->call('submit')->assertRedirect('/');

        $this->assertDatabaseHas('users', ['username' => 'budi_1', 'role' => 'member']);
        $this->assertAuthenticated();
    }

    public function test_register_validation(): void
    {
        User::factory()->create(['username' => 'dup', 'email' => 'dup@example.com']);

        Livewire::test(Register::class)
            ->set('username', 'dup')->set('email', 'dup@example.com')->set('password', 'short')
            ->call('submit')->assertHasErrors(['name', 'username', 'email', 'password']);
        $this->assertGuest();
    }
}
