<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Stok')]
class StockDetail extends Component
{
    use WithFileUploads;

    public Stock $stock;

    public bool $editing = false;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public string $quantity = '0';

    public string $unit = 'pcs';

    public string $price = '';

    public string $min_stock = '5';

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public function mount(Stock $stock): void
    {
        Gate::authorize('view', $stock);

        $this->stock = $stock;
        $this->fillForm();
    }

    private function fillForm(): void
    {
        $this->name = $this->stock->name;
        $this->sku = (string) $this->stock->sku;
        $this->description = (string) $this->stock->description;
        $this->quantity = (string) $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price !== null ? (string) ((float) $this->stock->price) : '';
        $this->min_stock = (string) $this->stock->min_stock;
    }

    public function startEdit(): void
    {
        $this->resetValidation();
        $this->fillForm();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->resetValidation();
        $this->editing = false;
    }

    public function save(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ]);

        $this->stock = $service->update($this->stock, [
            'name' => $data['name'],
            'sku' => $data['sku'] !== '' ? $data['sku'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'quantity' => (int) $data['quantity'],
            'unit' => $data['unit'],
            'price' => $data['price'] !== '' ? $data['price'] : null,
            'min_stock' => (int) $data['min_stock'],
        ]);

        $this->editing = false;
        $this->dispatch('notify', message: 'Perubahan disimpan.');
    }

    public function adjust(int $delta, StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $this->stock = $service->adjustQuantity($this->stock, $delta > 0 ? 1 : -1);
        $this->quantity = (string) $this->stock->quantity;
    }

    public function updatedCover(): void
    {
        Gate::authorize('update', $this->stock);

        $this->validate(['cover' => ['image', 'max:2048']]);

        if ($this->cover === null) {
            return;
        }

        $this->stock = app(StockService::class)->updateCover($this->stock, $this->cover);
        $this->reset('cover');
        $this->dispatch('notify', message: 'Cover diperbarui.');
    }

    public function removeCover(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $this->stock = $service->removeCover($this->stock);
        $this->dispatch('notify', message: 'Cover dihapus.');
    }

    public function delete(StockService $service): void
    {
        Gate::authorize('delete', $this->stock);

        $service->delete($this->stock);

        session()->flash('status', 'Stok dihapus.');
        $this->redirect(route('stocks.index'));
    }

    public function render(): View
    {
        return view('livewire.stock.stock-detail');
    }
}
