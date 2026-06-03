<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Space_whapichannel_details;

/**
 * Re-register the Chatterly webhook URL for every existing instance.
 *
 * The dev tunnel (cloudflared) hands out a new random https URL each time it starts,
 * so previously-created instances keep POSTing incoming messages / session events to a
 * dead URL. Run this (the tunnel script does it automatically on startup) to point every
 * instance at the current public webhook URL:
 *
 *   php artisan chatterly:webhook https://xxxx.trycloudflare.com/api/chatterly/webhook
 *   php artisan chatterly:webhook            # falls back to CHATTERLY_WEBHOOK_URL in .env
 */
class ChatterlyWebhook extends Command
{
    protected $signature = 'chatterly:webhook {url? : Public webhook URL (defaults to CHATTERLY_WEBHOOK_URL)}';
    protected $description = 'Re-register the Chatterly webhook URL for all instances';

    private string $token   = '45b189de454fe4c870ff11f22d874947617545e9ca9001fbd2db43091a10';
    private string $baseUrl = 'https://chatterly.easycoders.in/api';

    public function handle(): int
    {
        $url = $this->argument('url')
            ?: env('CHATTERLY_WEBHOOK_URL', rtrim(config('app.url'), '/') . '/api/chatterly/webhook');

        if (!$url) {
            $this->error('No webhook URL given and CHATTERLY_WEBHOOK_URL is empty.');
            return self::FAILURE;
        }

        $instances = Space_whapichannel_details::whereNotNull('name')->where('name', '!=', '')->get();
        if ($instances->isEmpty()) {
            $this->warn('No instances found to update.');
            return self::SUCCESS;
        }

        $this->info("Registering webhook: {$url}");
        foreach ($instances as $instance) {
            try {
                $resp = Http::timeout(20)->post(
                    "{$this->baseUrl}/instance/webhook/{$instance->name}",
                    ['token' => $this->token, 'webhookUrl' => $url]
                );
                $this->line("  [{$resp->status()}] {$instance->name}");
            } catch (\Throwable $e) {
                $this->error("  [ERR] {$instance->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
