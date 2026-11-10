<div class="space-y-5">
    <div>
        <h1 class="page-title">Pengguna</h1>
        <p class="page-sub">Daftar semua akun dan jumlah stok masing-masing. Admin hanya bisa melihat.</p>
    </div>

    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, username, atau email" class="field-input" style="padding-left: 2.25rem" aria-label="Cari pengguna">
    </div>

    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Jumlah stok</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($user->profile_photo_path)
                                        <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                    @else
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold text-white" style="background: var(--pine-soft)">{{ $user->initials() }}</span>
                                    @endif
                                    <div>
                                        <p class="font-semibold">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $user->username }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->isAdmin() ? 'bg-slate-800 text-white' : 'bg-teal-100 text-teal-800' }}">{{ $user->isAdmin() ? 'Admin' : 'Member' }}</span>
                            </td>
                            <td class="tabular">{{ $user->stocks_count }}</td>
                            <td class="text-right">
                                @unless ($user->isAdmin())
                                    <a href="{{ route('admin.users.stocks', $user) }}" class="btn btn-ghost btn-sm">Lihat stok</a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <p class="font-semibold">Tidak ada pengguna yang cocok</p>
                                <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-[color:var(--line)] px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>
</div>
