<?php

namespace Tests\Feature;

use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    private function fake(mixed $content, int $status = 200): void
    {
        config(['openai.api_key' => 'k', 'openai.base_url' => 'https://api.test/v1/', 'openai.model' => 'm']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status)]);
    }

    public function test_requires_api_key(): void
    {
        config(['openai.api_key' => null]);
        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_http_failure(): void
    {
        $this->fake('{}', 500);
        $this->expectExceptionMessage('Permintaan ke OpenAI gagal');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_empty_or_invalid_json(): void
    {
        foreach (['bukan json', json_encode(['items' => []]), json_encode(['items' => 'x'])] as $content) {
            $this->fake($content);
            try {
                (new OpenAiStockGeneratorService)->generate('x');
                $this->fail('harus melempar exception');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('AI tidak mengembalikan item', $e->getMessage());
            }
        }
    }

    public function test_success_filters_and_limits(): void
    {
        $this->fake(json_encode(['items' => [['name' => 'A'], 'bukan array', ['name' => 'B'], ['name' => 'C']]]));
        $items = (new OpenAiStockGeneratorService)->generate('x', 2);

        $this->assertCount(2, $items);
        $this->assertSame('A', $items[0]->name);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://api.test/v1/chat/completions'));
    }
}
