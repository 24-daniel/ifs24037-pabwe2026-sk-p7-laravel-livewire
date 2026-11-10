<?php

namespace App\Features\Profile\Livewire;

use App\Features\Profile\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Profil')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $photo;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
    }

    public function saveProfile(ProfileService $service): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore(Auth::id())],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(Auth::id())],
        ]);

        $service->updateProfile(Auth::user(), $data);

        session()->flash('status', 'Profil diperbarui.');
        $this->redirect(route('profile'));
    }

    public function savePassword(ProfileService $service): void
    {
        $data = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $service->updatePassword(Auth::user(), $data['password']);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->dispatch('notify', message: 'Password diganti.');
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'max:2048']]);

        app(ProfileService::class)->updatePhoto(Auth::user(), $this->photo);

        session()->flash('status', 'Foto profil diperbarui.');
        $this->redirect(route('profile'));
    }

    public function removePhoto(ProfileService $service): void
    {
        $service->removePhoto(Auth::user());

        session()->flash('status', 'Foto profil dihapus.');
        $this->redirect(route('profile'));
    }

    public function render(): View
    {
        return view('livewire.profile.profile', ['user' => Auth::user()]);
    }
}
