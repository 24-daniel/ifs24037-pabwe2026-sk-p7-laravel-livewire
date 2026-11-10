<div class="space-y-5">
    <div>
        <h1 class="page-title">Profil</h1>
        <p class="page-sub">Ubah data akun, foto, dan password.</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[18rem_1fr]">
        <div class="panel self-start p-6 text-center">
            @if ($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="Foto profil" class="mx-auto h-28 w-28 rounded-full object-cover">
            @else
                <span class="mx-auto flex h-28 w-28 items-center justify-center rounded-full text-3xl font-bold text-white" style="background: var(--pine-soft)">{{ $user->initials() }}</span>
            @endif
            <p class="mt-3 font-semibold">{{ $user->name }}</p>
            <p class="text-sm text-slate-500">{{ $user->isAdmin() ? 'Admin' : 'Member' }} · {{ $user->username }}</p>

            <div class="mt-4 flex justify-center gap-2">
                <label class="btn btn-ghost btn-sm cursor-pointer">
                    <span wire:loading.remove wire:target="photo">{{ $user->profile_photo_path ? 'Ganti foto' : 'Unggah foto' }}</span>
                    <span wire:loading wire:target="photo">Mengunggah…</span>
                    <input type="file" wire:model="photo" accept="image/*" class="sr-only">
                </label>
                @if ($user->profile_photo_path)
                    <button type="button" class="btn btn-danger btn-sm" wire:click="removePhoto" wire:confirm="Hapus foto profil?">Hapus</button>
                @endif
            </div>
            @error('photo')
                <p class="field-error">{{ $message }}</p>
            @enderror
            <p class="mt-3 text-xs text-slate-600">JPG atau PNG, maksimal 2 MB.</p>
        </div>

        <div class="space-y-5">
            <form wire:submit="saveProfile" class="panel p-6">
                <h2 class="text-lg font-bold">Data akun</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-field label="Nama lengkap" name="name" class="sm:col-span-2">
                        <input id="f-name" type="text" wire:model="name" class="field-input">
                    </x-field>
                    <x-field label="Username" name="username">
                        <input id="f-username" type="text" wire:model="username" class="field-input">
                    </x-field>
                    <x-field label="Email" name="email">
                        <input id="f-email" type="email" wire:model="email" class="field-input">
                    </x-field>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">Simpan profil</button>
                </div>
            </form>

            <form wire:submit="savePassword" class="panel p-6">
                <h2 class="text-lg font-bold">Ganti password</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-field label="Password saat ini" name="current_password" class="sm:col-span-2">
                        <input id="f-current_password" type="password" wire:model="current_password" autocomplete="current-password" class="field-input">
                    </x-field>
                    <x-field label="Password baru (min. 8 karakter)" name="password">
                        <input id="f-password" type="password" wire:model="password" autocomplete="new-password" class="field-input">
                    </x-field>
                    <x-field label="Ulangi password baru" name="password_confirmation">
                        <input id="f-password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" class="field-input">
                    </x-field>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="savePassword">Ganti password</button>
                </div>
            </form>
        </div>
    </div>
</div>
