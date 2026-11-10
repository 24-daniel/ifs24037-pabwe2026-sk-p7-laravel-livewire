<div class="panel p-7 shadow-sm">
    <h1 class="page-title">Buat akun</h1>
    <p class="page-sub">Akun baru otomatis menjadi member dan punya daftar stok sendiri.</p>

    <form wire:submit="submit" class="mt-6 space-y-4">
        <x-field label="Nama lengkap" name="name">
            <input id="f-name" type="text" wire:model="name" autocomplete="name" class="field-input">
        </x-field>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Username" name="username">
                <input id="f-username" type="text" wire:model="username" autocomplete="username" class="field-input">
            </x-field>
            <x-field label="Email" name="email">
                <input id="f-email" type="email" wire:model="email" autocomplete="email" class="field-input">
            </x-field>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Password (min. 8 karakter)" name="password">
                <input id="f-password" type="password" wire:model="password" autocomplete="new-password" class="field-input">
            </x-field>
            <x-field label="Ulangi password" name="password_confirmation">
                <input id="f-password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" class="field-input">
            </x-field>
        </div>

        <button id="register-submit" type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="submit">Daftar</span>
            <span wire:loading wire:target="submit">Membuat akun…</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-700">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold underline" style="color: var(--teal)">Masuk</a>
    </p>
</div>
