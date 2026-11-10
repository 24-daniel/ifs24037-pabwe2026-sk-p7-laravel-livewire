<div class="panel p-7 shadow-sm">
    <h1 class="page-title">Masuk</h1>
    <p class="page-sub">Kelola stok barangmu dari satu tempat.</p>

    <form wire:submit="submit" class="mt-6 space-y-4">
        <div>
            <label for="login-username" class="field-label">Username atau email</label>
            <input id="login-username" type="text" wire:model="login" autocomplete="username" class="field-input" autofocus>
            @error('login')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="login-password" class="field-label">Password</label>
            <input id="login-password" type="password" wire:model="password" autocomplete="current-password" class="field-input">
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300">
            Ingat saya di perangkat ini
        </label>

        <button id="login-submit" type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="submit">Masuk</span>
            <span wire:loading wire:target="submit">Memeriksa…</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-700">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold underline" style="color: var(--teal)">Daftar sekarang</a>
    </p>
</div>
