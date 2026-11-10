<?php

namespace App\Features\Ai\DTO;

final class GeneratedStockItem
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $sku,
        public readonly ?string $description,
        public readonly int $quantity,
        public readonly string $unit,
        public readonly ?float $price,
        public readonly int $minStock,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $name = trim((string) ($data['name'] ?? ''));
        $sku = trim((string) ($data['sku'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $unit = trim((string) ($data['unit'] ?? ''));
        $price = $data['price'] ?? null;
        $minStock = $data['min_stock'] ?? $data['minStock'] ?? 5;

        return new self(
            name: mb_substr($name !== '' ? $name : 'Barang tanpa nama', 0, 255),
            sku: $sku !== '' ? mb_substr($sku, 0, 255) : null,
            description: $description !== '' ? mb_substr($description, 0, 2000) : null,
            quantity: is_numeric($data['quantity'] ?? null) ? max(0, (int) $data['quantity']) : 0,
            unit: $unit !== '' ? mb_substr($unit, 0, 50) : 'pcs',
            price: is_numeric($price) ? max(0.0, (float) $price) : null,
            minStock: is_numeric($minStock) ? max(0, (int) $minStock) : 5,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
        ];
    }
}
