<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generate(string $topic, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY belum diisi di file .env.');
        }

        $system = 'Kamu membantu membuat daftar inventaris stok barang. '
            .'Balas HANYA dengan JSON berbentuk {"items":[{"name":"","sku":"","description":"","quantity":0,"unit":"","price":0,"min_stock":0}]}. '
            .'Gunakan bahasa Indonesia, harga dalam rupiah (angka saja), dan deskripsi singkat dalam Markdown.';

        $response = Http::withToken($apiKey)
            ->baseUrl(rtrim((string) config('openai.base_url'), '/'))
            ->timeout(60)
            ->post('/chat/completions', [
                'model' => config('openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => "Buatkan {$count} item inventaris untuk: {$topic}"],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Permintaan ke OpenAI gagal. Periksa API key dan koneksi internet.');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $items = is_array($decoded) ? ($decoded['items'] ?? []) : [];

        if (! is_array($items) || $items === []) {
            throw new RuntimeException('AI tidak mengembalikan item. Coba ubah topiknya.');
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->take($count)
            ->map(fn (array $item) => GeneratedStockItem::fromArray($item))
            ->values()
            ->all();
    }
}
