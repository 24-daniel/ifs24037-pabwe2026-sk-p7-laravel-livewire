@php
    $total = array_sum($counts);
    $filtering = $search !== '' || $statusFilter !== '';
@endphp

<div class="space-y-5">
    <div>
        <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Semua pengguna
        </a>
        <h1 class="page-title mt-2">Stok milik {{ $user->name }}</h1>
        <p class="page-sub">{{ $user->username }} · {{ $user->email }} · tampilan hanya-baca</p>
    </div>

    <div class="panel grid grid-cols-2 divide-x divide-y divide-[color:var(--line)] overflow-hidden sm:grid-cols-4 sm:divide-y-0">
        <div class="p-4">
            <p class="text-xs font-semibold text-slate-500">Semua barang</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="p-4">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>Tersedia</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $counts['available'] ?? 0 }}</p>
        </div>
        <div class="p-4">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-yellow-500"></span>Stok menipis</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $counts['low_stock'] ?? 0 }}</p>
        </div>
        <div class="p-4">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-red-500"></span>Habis</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $counts['out_of_stock'] ?? 0 }}</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-[14rem] flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU" class="field-input" style="padding-left: 2.25rem" aria-label="Cari stok">
        </div>
        <select wire:model.live="statusFilter" class="field-input" style="width: auto" aria-label="Filter status">
            <option value="">Semua status</option>
            <option value="available">Tersedia</option>
            <option value="low_stock">Stok menipis</option>
            <option value="out_of_stock">Habis</option>
        </select>
    </div>

    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        <tr wire:key="stock-{{ $stock->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($stock->cover_image_path)
                                        <img src="{{ asset('storage/'.$stock->cover_image_path) }}" alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover">
                                    @else
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold">{{ $stock->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $stock->sku ?: 'Tanpa SKU' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="tabular whitespace-nowrap">{{ $stock->quantity }} {{ $stock->unit }}</td>
                            <td class="tabular whitespace-nowrap">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '–' }}</td>
                            <td><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center">
                                <p class="font-semibold">{{ $filtering ? 'Tidak ada stok yang cocok' : 'Pengguna ini belum punya stok' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($stocks->hasPages())
            <div class="border-t border-[color:var(--line)] px-4 py-3">{{ $stocks->links() }}</div>
        @endif
    </div>
</div>
