<?php

namespace App\Features\Stock\Contracts;

use App\Features\Stock\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Stock>
     */
    public function paginateForUser(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * @return Collection<int, Stock>
     */
    public function allForUser(int $userId): Collection;

    /**
     * @return array<string, int>
     */
    public function statusCounts(int $userId): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Stock;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Stock $stock, array $data): Stock;

    public function delete(Stock $stock): void;
}
