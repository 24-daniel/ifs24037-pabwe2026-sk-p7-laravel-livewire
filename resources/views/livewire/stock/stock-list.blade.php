@php
    $total = array_sum($counts);
    $available = $counts['available'] ?? 0;
    $low = $counts['low_stock'] ?? 0;
    $out = $counts['out_of_stock'] ?? 0;
    $filtering = $search !== '' || $statusFilter !== '';
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="page-title">Stok barang</h1>
            <p class="page-sub">Catat, cari, dan atur jumlah barang milikmu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-ghost" wire:click="export" wire:loading.attr="disabled" wire:target="export">Ekspor Excel</button>
            <button type="button" class="btn btn-ghost" wire:click="openImport">Impor Excel</button>
            <button type="button" class="btn btn-ghost" wire:click="openAi">Buat dengan AI</button>
            <button type="button" class="btn btn-primary" wire:click="openCreate">Tambah stok</button>
        </div>
    </div>

    <div class="panel grid grid-cols-2 divide-x divide-y divide-[color:var(--line)] overflow-hidden sm:grid-cols-4 sm:divide-y-0">
        <button type="button" wire:click="$set('statusFilter', '')" class="p-4 text-left hover:bg-slate-50">
            <p class="text-xs font-semibold text-slate-500">Semua barang</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $total }}</p>
        </button>
        <button type="button" wire:click="$set('statusFilter', 'available')" class="p-4 text-left hover:bg-slate-50">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>Tersedia</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $available }}</p>
        </button>
        <button type="button" wire:click="$set('statusFilter', 'low_stock')" class="p-4 text-left hover:bg-slate-50">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-yellow-500"></span>Stok menipis</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $low }}</p>
        </button>
        <button type="button" wire:click="$set('statusFilter', 'out_of_stock')" class="p-4 text-left hover:bg-slate-50">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-red-500"></span>Habis</p>
            <p class="tabular mt-1 text-2xl font-bold">{{ $out }}</p>
        </button>
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
        @if ($filtering)
            <button type="button" class="btn btn-ghost" wire:click="resetFilters">Reset filter</button>
        @endif
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
                        <th class="text-right">Aksi</th>
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
                                        <a href="{{ route('stocks.show', $stock) }}" class="block truncate font-semibold hover:underline" style="color: var(--teal-dark)">{{ $stock->name }}</a>
                                        <p class="truncate text-xs text-slate-500">{{ $stock->sku ?: 'Tanpa SKU' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="stepper tabular">
                                    <button type="button" wire:click="adjust({{ $stock->id }}, -1)" wire:loading.attr="disabled" @disabled($stock->quantity <= 0) aria-label="Kurangi satu {{ $stock->name }}">−</button>
                                    <span>{{ $stock->quantity }} <span class="text-xs font-normal text-slate-500">{{ $stock->unit }}</span></span>
                                    <button type="button" wire:click="adjust({{ $stock->id }}, 1)" wire:loading.attr="disabled" aria-label="Tambah satu {{ $stock->name }}">+</button>
                                </div>
                            </td>
                            <td class="tabular whitespace-nowrap">
                                {{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '–' }}
                            </td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('stocks.show', $stock) }}" class="btn btn-ghost btn-sm">Detail</a>
                                    <button type="button" class="btn btn-ghost btn-sm" wire:click="openEdit({{ $stock->id }})">Edit</button>
                                    <button type="button" class="btn btn-danger btn-sm" wire:click="delete({{ $stock->id }})" wire:confirm="Hapus {{ $stock->name }}? Tindakan ini tidak bisa dibatalkan.">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-14 text-center">
                                @if ($filtering)
                                    <p class="font-semibold">Tidak ada stok yang cocok</p>
                                    <p class="mt-1 text-sm text-slate-500">Ubah kata kunci atau reset filter.</p>
                                @else
                                    <p class="font-semibold">Belum ada stok</p>
                                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Tambahkan barang pertamamu, impor dari file Excel, atau minta AI membuatkan daftar awal.</p>
                                    <div class="mt-4 flex justify-center gap-2">
                                        <button type="button" class="btn btn-primary" wire:click="openCreate">Tambah stok</button>
                                        <button type="button" class="btn btn-ghost" wire:click="openImport">Impor Excel</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($stocks->hasPages())
            <div class="border-t border-[color:var(--line)] px-4 py-3">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>

    {{-- Modal: tambah / edit --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center" x-data x-on:keydown.escape.window="$wire.closeForm()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeForm"></div>
            <form wire:submit="save" class="panel relative my-6 w-full max-w-2xl p-6 shadow-xl">
                <h2 class="text-lg font-bold">{{ $editingId ? 'Edit stok' : 'Tambah stok' }}</h2>
                <p class="page-sub">Status dihitung otomatis dari jumlah dan batas stok menipis.</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-field label="Nama barang" name="name" class="sm:col-span-2">
                        <input id="f-name" type="text" wire:model="name" class="field-input" autofocus>
                    </x-field>
                    <x-field label="SKU (opsional)" name="sku">
                        <input id="f-sku" type="text" wire:model="sku" class="field-input">
                    </x-field>
                    <x-field label="Harga satuan (Rp)" name="price">
                        <input id="f-price" type="number" min="0" step="any" wire:model="price" class="field-input">
                    </x-field>
                    <x-field label="Jumlah" name="quantity">
                        <input id="f-quantity" type="number" min="0" wire:model="quantity" class="field-input">
                    </x-field>
                    <x-field label="Satuan" name="unit">
                        <input id="f-unit" type="text" wire:model="unit" class="field-input" placeholder="pcs, box, kg">
                    </x-field>
                    <x-field label="Batas stok menipis" name="min_stock">
                        <input id="f-min_stock" type="number" min="0" wire:model="min_stock" class="field-input">
                    </x-field>
                    <x-field label="Deskripsi (Markdown)" name="description" class="sm:col-span-2">
                        <textarea id="f-description" rows="4" wire:model="description" class="field-input" placeholder="Boleh pakai **tebal**, daftar, dan tautan"></textarea>
                    </x-field>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" wire:click="closeForm">Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Simpan stok</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal: impor Excel --}}
    @if ($showImport)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center" x-data x-on:keydown.escape.window="$wire.closeImport()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeImport"></div>
            <form wire:submit="import" class="panel relative my-6 w-full max-w-lg p-6 shadow-xl">
                <h2 class="text-lg font-bold">Impor dari Excel</h2>
                <p class="page-sub">Kolom yang dibaca: Name, SKU, Quantity, Unit, Price, Min Stock, Description. Paling mudah, ekspor dulu lalu pakai file itu sebagai contoh.</p>

                <div class="mt-5">
                    <label for="f-importFile" class="field-label">File (.xlsx, .xls, .csv — maks. 2 MB)</label>
                    <input id="f-importFile" type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="field-input">
                    <div wire:loading wire:target="importFile" class="mt-1 text-sm text-slate-500">Mengunggah file…</div>
                    @error('importFile')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" wire:click="closeImport">Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="import,importFile">
                        <span wire:loading.remove wire:target="import">Impor stok</span>
                        <span wire:loading wire:target="import">Mengimpor…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal: buat dengan AI --}}
    @if ($showAi)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center" x-data x-on:keydown.escape.window="$wire.closeAi()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeAi"></div>
            <div class="panel relative my-6 w-full max-w-2xl p-6 shadow-xl">
                <h2 class="text-lg font-bold">Buat daftar stok dengan AI</h2>
                <p class="page-sub">Tulis jenis usahamu, lalu pilih barang yang mau disimpan.</p>

                <form wire:submit="generateAi" class="mt-5 grid gap-4 sm:grid-cols-[1fr_8rem_auto] sm:items-end">
                    <x-field label="Topik" name="aiTopic">
                        <input id="f-aiTopic" type="text" wire:model="aiTopic" class="field-input" placeholder="Contoh: perlengkapan kantor kecil">
                    </x-field>
                    <x-field label="Jumlah item" name="aiCount">
                        <input id="f-aiCount" type="number" min="1" max="10" wire:model="aiCount" class="field-input">
                    </x-field>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="generateAi">
                        <span wire:loading.remove wire:target="generateAi">Buat daftar</span>
                        <span wire:loading wire:target="generateAi">Memproses…</span>
                    </button>
                </form>

                @if ($aiError)
                    <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $aiError }}</p>
                @endif

                @if (count($aiItems) > 0)
                    <div class="mt-5 divide-y divide-[color:var(--line)] rounded-lg border border-[color:var(--line)]">
                        @foreach ($aiItems as $index => $item)
                            <label class="flex cursor-pointer items-start gap-3 p-3 hover:bg-slate-50" wire:key="ai-{{ $index }}">
                                <input type="checkbox" wire:model="aiSelected" value="{{ $index }}" class="mt-1 rounded border-slate-300">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold">{{ $item['name'] }}</span>
                                    <span class="block text-xs text-slate-500">
                                        {{ $item['sku'] ?: 'Tanpa SKU' }} · {{ $item['quantity'] }} {{ $item['unit'] }}
                                        @if ($item['price'] !== null) · Rp {{ number_format((float) $item['price'], 0, ',', '.') }} @endif
                                    </span>
                                    @if ($item['description'])
                                        <span class="mt-1 block text-xs text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($item['description']), 140) }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" wire:click="closeAi">Tutup</button>
                    @if (count($aiItems) > 0)
                        <button type="button" class="btn btn-primary" wire:click="saveAi" wire:loading.attr="disabled" wire:target="saveAi" @disabled(count($aiSelected) === 0)>
                            Simpan {{ count($aiSelected) }} stok terpilih
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
