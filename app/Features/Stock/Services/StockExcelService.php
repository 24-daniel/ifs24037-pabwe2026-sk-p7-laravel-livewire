<?php

namespace App\Features\Stock\Services;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockExcelService
{
    public const HEADERS = ['Name', 'SKU', 'Quantity', 'Unit', 'Price', 'Min Stock', 'Status', 'Description'];

    private const ALIASES = [
        'name' => 'name',
        'nama' => 'name',
        'sku' => 'sku',
        'quantity' => 'quantity',
        'qty' => 'quantity',
        'jumlah' => 'quantity',
        'unit' => 'unit',
        'satuan' => 'unit',
        'price' => 'price',
        'harga' => 'price',
        'min stock' => 'min_stock',
        'min_stock' => 'min_stock',
        'minstock' => 'min_stock',
        'description' => 'description',
        'deskripsi' => 'description',
    ];

    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  iterable<\App\Features\Stock\Models\Stock>  $stocks
     */
    public function export(iterable $stocks): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stok');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;
        foreach ($stocks as $stock) {
            $sheet->fromArray([
                $stock->name,
                $stock->sku,
                $stock->quantity,
                $stock->unit,
                $stock->price !== null ? (float) $stock->price : null,
                $stock->min_stock,
                $stock->status->label(),
                $stock->description,
            ], null, 'A'.$row);
            $row++;
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');

        return (string) ob_get_clean();
    }

    public function import(User $user, string $path): int
    {
        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $headerRow = array_shift($rows) ?? [];
        $columns = [];
        foreach ($headerRow as $index => $title) {
            $key = strtolower(trim((string) $title));
            if (isset(self::ALIASES[$key])) {
                $columns[self::ALIASES[$key]] = $index;
            }
        }

        if (! isset($columns['name'])) {
            return 0;
        }

        $imported = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row[$columns['name']] ?? ''));
            if ($name === '') {
                continue;
            }

            $get = fn (string $field) => isset($columns[$field]) ? ($row[$columns[$field]] ?? null) : null;

            $quantity = $get('quantity');
            $minStock = $get('min_stock');
            $price = $get('price');
            $sku = trim((string) $get('sku'));
            $unit = trim((string) $get('unit'));
            $description = trim((string) $get('description'));

            $this->stockService->create($user, [
                'name' => mb_substr($name, 0, 255),
                'sku' => $sku !== '' ? mb_substr($sku, 0, 255) : null,
                'description' => $description !== '' ? $description : null,
                'quantity' => is_numeric($quantity) ? max(0, (int) $quantity) : 0,
                'unit' => $unit !== '' ? mb_substr($unit, 0, 50) : 'pcs',
                'price' => is_numeric($price) ? max(0, (float) $price) : null,
                'min_stock' => is_numeric($minStock) ? max(0, (int) $minStock) : 5,
            ]);
            $imported++;
        }

        return $imported;
    }
}
