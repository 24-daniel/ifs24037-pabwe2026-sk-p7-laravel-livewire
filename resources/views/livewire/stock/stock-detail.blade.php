@push('head')
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
@endpush

<div class="space-y-5">
    <div>
        <a href="{{ route('stocks.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Semua stok
        </a>
        <h1 class="page-title mt-2">{{ $stock->name }}</h1>
        <p class="page-sub">{{ $stock->sku ?: 'Tanpa SKU' }}</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[20rem_1fr]">
        {{-- Kiri: cover dan ringkasan --}}
        <div class="panel overflow-hidden self-start">
            <div class="relative flex aspect-[4/3] items-center justify-center bg-slate-100">
                @if ($stock->cover_image_path)
                    <img src="{{ asset('storage/'.$stock->cover_image_path) }}" alt="Cover {{ $stock->name }}" class="h-full w-full object-cover">
                @else
                    <div class="text-center text-slate-600">
                        <svg class="mx-auto h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zm10.5-11.25h.008v.008h-.008V9.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                        <p class="mt-1 text-sm">Belum ada cover</p>
                    </div>
                @endif
                <div wire:loading wire:target="cover" class="absolute inset-0 flex items-center justify-center bg-white/70 text-sm font-medium">Mengunggah…</div>
            </div>

            <div class="flex gap-2 border-b border-[color:var(--line)] p-3">
                <label class="btn btn-ghost btn-sm flex-1 cursor-pointer">
                    {{ $stock->cover_image_path ? 'Ganti cover' : 'Unggah cover' }}
                    <input type="file" wire:model="cover" accept="image/*" class="sr-only">
                </label>
                @if ($stock->cover_image_path)
                    <button type="button" class="btn btn-danger btn-sm" wire:click="removeCover" wire:confirm="Hapus cover ini?">Hapus</button>
                @endif
            </div>
            @error('cover')
                <p class="field-error px-3 pt-2">{{ $message }}</p>
            @enderror

            <dl class="space-y-4 p-4 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">Jumlah</dt>
                    <dd>
                        <div class="stepper tabular">
                            <button type="button" wire:click="adjust(-1)" wire:loading.attr="disabled" @disabled($stock->quantity <= 0) aria-label="Kurangi satu">−</button>
                            <span>{{ $stock->quantity }} <span class="text-xs font-normal text-slate-500">{{ $stock->unit }}</span></span>
                            <button type="button" wire:click="adjust(1)" wire:loading.attr="disabled" aria-label="Tambah satu">+</button>
                        </div>
                    </dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Status</dt>
                    <dd><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Harga satuan</dt>
                    <dd class="tabular font-semibold">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '–' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Batas stok menipis</dt>
                    <dd class="tabular font-semibold">{{ $stock->min_stock }} {{ $stock->unit }}</dd>
                </div>
            </dl>
        </div>

        {{-- Kanan: deskripsi / form edit --}}
        <div class="panel p-6">
            @if (! $editing)
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-bold">Deskripsi</h2>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" wire:click="startEdit">Edit stok</button>
                        <button type="button" class="btn btn-danger btn-sm" wire:click="delete" wire:confirm="Hapus {{ $stock->name }}? Tindakan ini tidak bisa dibatalkan.">Hapus</button>
                    </div>
                </div>

                @if (filled($stock->description))
                    <div class="md">{!! \Illuminate\Support\Str::markdown($stock->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                @else
                    <div class="rounded-lg border border-dashed border-[color:var(--line)] p-8 text-center">
                        <p class="font-semibold">Belum ada deskripsi</p>
                        <p class="mt-1 text-sm text-slate-500">Tulis spesifikasi atau catatan barang ini. Format Markdown didukung.</p>
                        <button type="button" class="btn btn-ghost btn-sm mt-3" wire:click="startEdit">Tulis deskripsi</button>
                    </div>
                @endif
            @else
                <form wire:submit="save">
                    <h2 class="mb-4 text-lg font-bold">Edit stok</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Nama barang" name="name" class="sm:col-span-2">
                            <input id="f-name" type="text" wire:model="name" class="field-input">
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
                            <input id="f-unit" type="text" wire:model="unit" class="field-input">
                        </x-field>
                        <x-field label="Batas stok menipis" name="min_stock">
                            <input id="f-min_stock" type="number" min="0" wire:model="min_stock" class="field-input">
                        </x-field>
                    </div>

                    <div class="mt-4">
                        <label class="field-label">Deskripsi (Markdown)</label>
                        <div wire:ignore
                             x-data="{ editor: null }"
                             x-init="
                                if (window.toastui) {
                                    $refs.fallback.classList.add('hidden');
                                    editor = new toastui.Editor({
                                        el: $refs.editor,
                                        height: '320px',
                                        initialEditType: 'markdown',
                                        previewStyle: 'tab',
                                        usageStatistics: false,
                                        initialValue: $wire.description ?? ''
                                    });
                                    editor.on('change', () => { $wire.description = editor.getMarkdown(); });
                                }
                             ">
                            <div x-ref="editor"></div>
                            <textarea x-ref="fallback" id="f-description" rows="10" wire:model="description" class="field-input"></textarea>
                        </div>
                        @error('description')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" class="btn btn-ghost" wire:click="cancelEdit">Batal</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Simpan perubahan</span>
                            <span wire:loading wire:target="save">Menyimpan…</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
