<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class StockRepository implements StockRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage);
    }

    public function allForUser(int $userId): Collection
    {
        return Stock::query()->where('user_id', $userId)->orderBy('name')->get();
    }

    public function statusCounts(int $userId): array
    {
        $rows = Stock::query()
            ->where('user_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->toBase()
            ->get();

        return $rows->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();
    }

    public function create(array $data): Stock
    {
        return Stock::query()->create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update($data);

        return $stock->refresh();
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
