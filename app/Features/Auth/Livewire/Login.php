<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\LoginUserAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Login extends Component
{
    #[Validate('required|string')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function submit(LoginUserAction $action): void
    {
        $this->validate();

        if (! $action->execute($this->login, $this->password, $this->remember)) {
            $this->addError('login', 'Username/email atau password salah.');

            return;
        }

        session()->regenerate();

        $this->redirect('/');
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}