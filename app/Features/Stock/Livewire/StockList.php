<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Stok Barang')]
class StockList extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public string $quantity = '0';

    public string $unit = 'pcs';

    public string $price = '';

    public string $min_stock = '5';

    public bool $showImport = false;

    /** @var TemporaryUploadedFile|null */
    public $importFile = null;

    public bool $showAi = false;

    public string $aiTopic = '';

    public int $aiCount = 5;

    /** @var array<int, array<string, mixed>> */
    public array $aiItems = [];

    /** @var array<int, string> */
    public array $aiSelected = [];

    public ?string $aiError = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function formRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'sku', 'description', 'price']);
        $this->quantity = '0';
        $this->unit = 'pcs';
        $this->min_stock = '5';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $stock = $this->ownedStock($id);

        $this->resetValidation();
        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = (string) $stock->sku;
        $this->description = (string) $stock->description;
        $this->quantity = (string) $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price !== null ? (string) ((float) $stock->price) : '';
        $this->min_stock = (string) $stock->min_stock;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function save(StockService $service): void
    {
        $data = $this->validate($this->formRules());

        $payload = [
            'name' => $data['name'],
            'sku' => $data['sku'] !== '' ? $data['sku'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'quantity' => (int) $data['quantity'],
            'unit' => $data['unit'],
            'price' => $data['price'] !== '' ? $data['price'] : null,
            'min_stock' => (int) $data['min_stock'],
        ];

        if ($this->editingId !== null) {
            $service->update($this->ownedStock($this->editingId), $payload);
            $message = 'Stok diperbarui.';
        } else {
            Gate::authorize('create', Stock::class);
            $service->create(Auth::user(), $payload);
            $message = 'Stok ditambahkan.';
        }

        $this->closeForm();
        $this->dispatch('notify', message: $message);
    }

    public function adjust(int $id, int $delta, StockService $service): void
    {
        $service->adjustQuantity($this->ownedStock($id), $delta > 0 ? 1 : -1);
    }

    public function delete(int $id, StockService $service): void
    {
        $service->delete($this->ownedStock($id));

        $this->dispatch('notify', message: 'Stok dihapus.');
    }

    public function export(StockExcelService $excel, StockRepositoryInterface $repository): StreamedResponse
    {
        $content = $excel->export($repository->allForUser((int) Auth::id()));

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, 'stok-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function openImport(): void
    {
        $this->resetValidation();
        $this->reset('importFile');
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->showImport = false;
        $this->reset('importFile');
    }

    public function import(StockExcelService $excel): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:2048'],
        ]);

        if ($this->importFile === null) {
            return;
        }

        try {
            $count = $excel->import(Auth::user(), (string) $this->importFile->getRealPath());
        } catch (Throwable) {
            $this->addError('importFile', 'File tidak bisa dibaca. Pakai file hasil Ekspor sebagai contoh format.');

            return;
        }

        if ($count === 0) {
            $this->addError('importFile', 'Tidak ada baris yang bisa diimpor. Pastikan ada kolom Name.');

            return;
        }

        $this->closeImport();
        $this->dispatch('notify', message: "{$count} stok berhasil diimpor.");
    }

    public function openAi(): void
    {
        $this->resetValidation();
        $this->reset(['aiItems', 'aiSelected', 'aiError']);
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->showAi = false;
    }

    public function generateAi(AiClientInterface $ai): void
    {
        $this->validate([
            'aiTopic' => ['required', 'string', 'max:200'],
            'aiCount' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $this->reset(['aiItems', 'aiSelected', 'aiError']);

        try {
            $items = $ai->generate($this->aiTopic, $this->aiCount);
        } catch (RuntimeException $e) {
            $this->aiError = $e->getMessage();

            return;
        } catch (Throwable) {
            $this->aiError = 'Terjadi kesalahan saat menghubungi AI. Coba lagi.';

            return;
        }

        $this->aiItems = array_map(fn (GeneratedStockItem $item) => $item->toArray(), $items);
        $this->aiSelected = array_map('strval', array_keys($this->aiItems));
    }

    public function saveAi(StockService $service): void
    {
        Gate::authorize('create', Stock::class);

        $saved = 0;
        foreach ($this->aiSelected as $index) {
            $raw = $this->aiItems[(int) $index] ?? null;

            if (! is_array($raw)) {
                continue;
            }

            $service->create(Auth::user(), GeneratedStockItem::fromArray($raw)->toArray());
            $saved++;
        }

        $this->reset(['aiItems', 'aiSelected', 'aiTopic']);
        $this->showAi = false;
        $this->dispatch('notify', message: "{$saved} stok dari AI disimpan.");
    }

    private function ownedStock(int $id): Stock
    {
        $stock = Stock::query()->findOrFail($id);
        Gate::authorize('update', $stock);

        return $stock;
    }

    public function render(StockRepositoryInterface $repository): View
    {
        $userId = (int) Auth::id();

        return view('livewire.stock.stock-list', [
            'stocks' => $repository->paginateForUser(
                $userId,
                $this->search !== '' ? $this->search : null,
                $this->statusFilter !== '' ? $this->statusFilter : null,
                10,
            ),
            'counts' => $repository->statusCounts($userId),
        ]);
    }
}