<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Stok Pengguna')]
class AdminUserStockList extends Component
{
    use WithPagination;

    public User $user;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(AdminUserService $service): View
    {
        return view('livewire.admin.admin-user-stock-list', [
            'stocks' => $service->paginateStocksOf(
                $this->user,
                $this->search !== '' ? $this->search : null,
                $this->statusFilter !== '' ? $this->statusFilter : null,
                10,
            ),
            'counts' => $service->statusCountsOf($this->user),
        ]);
    }
}
