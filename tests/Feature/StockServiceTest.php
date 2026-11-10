<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_and_repository(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $svc = app(StockService::class);

        $s = $svc->create($user, ['name' => 'A']);
        $this->assertSame('out_of_stock', $s->status->value);
        $this->assertSame('pcs', $s->unit);

        $s = $svc->update($s, ['name' => 'A2']);
        $this->assertSame('A2', $s->name);

        $s = $svc->update($s, ['name' => 'A3', 'quantity' => 20, 'min_stock' => 2]);
        $this->assertSame('available', $s->status->value);

        $s = $svc->updateCover($s, UploadedFile::fake()->image('c.jpg'));
        $old = $s->cover_image_path;
        $s = $svc->updateCover($s, UploadedFile::fake()->image('d.jpg'));
        Storage::disk('public')->assertMissing($old);

        $svc->delete($s);
        $this->assertModelMissing($s);

        $repo = new StockRepository;
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Z', 'status' => 'available']);
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'B', 'status' => 'low_stock']);
        $this->assertSame(['B', 'Z'], $repo->allForUser($user->id)->pluck('name')->all());
        $this->assertSame(['available' => 1, 'low_stock' => 1], $repo->statusCounts($user->id));
        $this->assertCount(1, $repo->paginateForUser($user->id, 'Z', 'available'));
    }
}
