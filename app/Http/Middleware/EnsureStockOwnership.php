<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $stock = $request->route('stock');

        if (! $stock instanceof Stock) {
            $stock = is_numeric($stock) ? Stock::query()->find((int) $stock) : null;

            if (! $stock) {
                abort(404);
            }
        }

        if (! $request->user() || $request->user()->id !== $stock->user_id) {
            abort(403);
        }

        return $next($request);
    }
}