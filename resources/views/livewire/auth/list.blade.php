<div>
    <h1 class="mb-4 text-xl font-semibold">Daftar Stok</h1>

    <div class="overflow-x-auto rounded bg-white shadow">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50">
                <tr>
                    <th class="p-3">Nama</th>
                    <th class="p-3">SKU</th>
                    <th class="p-3">Jumlah</th>
                    <th class="p-3">Harga</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    <tr class="border-b">
                        <td class="p-3">{{ $stock->name }}</td>
                        <td class="p-3">{{ $stock->sku }}</td>
                        <td class="p-3">{{ $stock->quantity }} {{ $stock->unit }}</td>
                        <td class="p-3">
                            {{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}
                        </td>
                        <td class="p-3">
                            <span class="rounded px-2 py-1 text-xs {{ $stock->status->badgeClass() }}">
                                {{ $stock->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-500">Belum ada stok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>