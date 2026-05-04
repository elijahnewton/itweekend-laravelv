<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_github_webhook_returns_ok_for_push_to_main(): void
    {
        $payload = json_encode([
            'ref' => 'refs/heads/main',
            'commits' => [],
        ]);

        $response = $this->postJson('/api/webhooks/github', json_decode($payload, true), [
            'X-GitHub-Event' => 'push',
        ]);

        // Since content directory may not have courses in test env, just check 200
        $response->assertOk();
        $response->assertJsonStructure(['message']);
    }

    public function test_github_webhook_returns_ok_for_non_push_event(): void
    {
        $response = $this->postJson('/api/webhooks/github', [], [
            'X-GitHub-Event' => 'ping',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Event received.');
    }

    public function test_github_webhook_rejects_invalid_signature_when_secret_configured(): void
    {
        // Set a webhook secret in config
        config(['services.github.webhook_secret' => 'test-secret']);

        $payload = json_encode(['ref' => 'refs/heads/main']);

        $response = $this->withHeaders([
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => 'sha256=invalidsignature',
        ])->postJson('/api/webhooks/github', json_decode($payload, true));

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Invalid signature.');
    }

    public function test_github_webhook_accepts_valid_signature(): void
    {
        $secret = 'test-secret-key';
        config(['services.github.webhook_secret' => $secret]);

        $payload = ['ref' => 'refs/heads/main'];
        $payloadJson = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, $secret);

        // Use withToken to set headers and postJson to send the payload
        // The controller computes hmac on $request->getContent() which is the raw JSON
        $response = $this->withHeaders([
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
        ])->postJson('/api/webhooks/github', $payload);

        $response->assertOk();
    }
}
