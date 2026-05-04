<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContentIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function github(Request $request, ContentIngestionService $service): JsonResponse
    {
        $secret = config('services.github.webhook_secret');

        if ($secret) {
            $signature = $request->header('X-Hub-Signature-256', '');
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

            if (! hash_equals($expected, $signature)) {
                return response()->json(['error' => 'Invalid signature.'], 403);
            }
        }

        $event = $request->header('X-GitHub-Event', '');

        // Only trigger sync on push events to the main branch
        if ($event === 'push') {
            $payload = $request->json()->all();
            $ref = $payload['ref'] ?? '';

            if (in_array($ref, ['refs/heads/main', 'refs/heads/master'], true)) {
                $path = storage_path('app/content');
                $count = $service->syncFromContentDirectory($path);

                Log::info("GitHub webhook triggered content sync: {$count} lessons synced.");

                return response()->json(['message' => "Synced {$count} lessons."]);
            }
        }

        return response()->json(['message' => 'Event received.']);
    }
}
