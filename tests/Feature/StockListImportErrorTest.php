<?php

namespace Tests\Feature;

use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StockListImportErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_unreadable_file_shows_error(): void
    {
        Storage::fake('public');

        $this->mock(StockExcelService::class, function ($mock) {
            $mock->shouldReceive('import')->once()->andThrow(new RuntimeException('rusak'));
        });

        Livewire::actingAs(User::factory()->create())->test(StockList::class)
            ->set('importFile', UploadedFile::fake()->create('a.xlsx', 10))
            ->call('import')
            ->assertHasErrors('importFile')
            ->assertNotDispatched('notify');
    }

    public function test_import_with_zero_rows_shows_error(): void
    {
        Storage::fake('public');

        $this->mock(StockExcelService::class, function ($mock) {
            $mock->shouldReceive('import')->once()->andReturn(0);
        });

        Livewire::actingAs(User::factory()->create())->test(StockList::class)
            ->set('importFile', UploadedFile::fake()->create('a.xlsx', 10))
            ->call('import')
            ->assertHasErrors('importFile')
            ->assertNotDispatched('notify');
    }
}