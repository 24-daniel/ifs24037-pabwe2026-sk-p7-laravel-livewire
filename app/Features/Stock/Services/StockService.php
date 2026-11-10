<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Stock
    {
        $quantity = (int) ($data['quantity'] ?? 0);
        $minStock = (int) ($data['min_stock'] ?? 5);

        return $this->repository->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'quantity' => $quantity,
            'unit' => $data['unit'] ?? 'pcs',
            'price' => $data['price'] ?? null,
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Stock $stock, array $data): Stock
    {
        $quantity = (int) ($data['quantity'] ?? $stock->quantity);
        $minStock = (int) ($data['min_stock'] ?? $stock->min_stock);

        return $this->repository->update($stock, array_merge($data, [
            'quantity' => $quantity,
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock),
        ]));
    }

    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $quantity = max(0, $stock->quantity + $delta);

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock),
        ]);
    }

    public function updateCover(Stock $stock, UploadedFile $file): Stock
    {
        $this->deleteCoverFile($stock);

        $path = $file->store('stock-covers', 'public');

        return $this->repository->update($stock, ['cover_image_path' => $path]);
    }

    public function removeCover(Stock $stock): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => null]);
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCoverFile($stock);
        $this->repository->delete($stock);
    }

    private function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }
}
