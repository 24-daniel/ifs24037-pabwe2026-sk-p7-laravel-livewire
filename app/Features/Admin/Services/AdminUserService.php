<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(private readonly StockRepositoryInterface $stocks) {}

    public function paginateUsers(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount('stocks')
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function paginateStocksOf(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stocks->paginateForUser($user->id, $search, $status, $perPage);
    }

    /**
     * @return array<string, int>
     */
    public function statusCountsOf(User $user): array
    {
        return $this->stocks->statusCounts($user->id);
    }
}
