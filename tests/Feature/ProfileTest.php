<?php

namespace Tests\Feature;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_mount_and_save_profile(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->assertSet('name', $user->name)->assertSet('email', $user->email)
            ->set('name', 'Nama Baru')->set('username', 'baru_1')->set('email', 'baru@example.com')
            ->call('saveProfile')->assertRedirect(route('profile'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru', 'username' => 'baru_1']);
    }

    public function test_save_profile_validation(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)->test(Profile::class)->set('name', '')->call('saveProfile')->assertHasErrors('name');
    }

    public function test_save_password(): void
    {
        $user = User::factory()->create();
        $c = Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'password')->set('password', 'newpass123')->set('password_confirmation', 'newpass123')
            ->call('savePassword')->assertHasNoErrors()->assertDispatched('notify');

        $c->assertSet('password', '');
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));

        Livewire::actingAs($user)->test(Profile::class)->set('current_password', 'x')->set('password', 'newpass123')
            ->set('password_confirmation', 'newpass123')->call('savePassword')->assertHasErrors('current_password');
    }

    public function test_photo_upload_replace_and_remove(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('a.jpg'))->assertRedirect(route('profile'));
        $first = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        Livewire::actingAs($user->fresh())->test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);

        Livewire::actingAs($user->fresh())->test(Profile::class)->call('removePhoto')->assertRedirect(route('profile'));
        $this->assertNull($user->fresh()->profile_photo_path);

        // hapus saat belum ada foto
        Livewire::actingAs($user->fresh())->test(Profile::class)->call('removePhoto');
    }

    public function test_photo_invalid_and_null(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('photo', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))->assertHasErrors('photo');
        Livewire::actingAs($user)->test(Profile::class)->call('updatedPhoto')->assertNoRedirect();
    }
}
