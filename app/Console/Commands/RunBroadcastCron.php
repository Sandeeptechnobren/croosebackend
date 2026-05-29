<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BroadcastHeader;
use App\Models\TargetMessage;
use App\Services\MessageService;
use Illuminate\Support\Collection;
use App\Models\Space_whapichannel_details;

class RunBroadcastCron extends Command
{
    protected $signature = 'broadcast:run';
    protected $description = 'Run scheduled broadcasts';

    public function handle()
    {
        $this->info('Broadcast cron started');

        $broadcasts = BroadcastHeader::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($broadcasts->isEmpty()) {
            $this->info('No broadcasts to process');
            return Command::SUCCESS;
        }

        foreach ($broadcasts as $broadcast) {

            $this->info("Processing Broadcast ID: {$broadcast->id}");

            // 🔥 space id auto detect
            $spaceId = $broadcast->space_id;

            if (!$spaceId) {
                $spaceId = Space_whapichannel_details::value('space_id');
            }

            if (!$spaceId) {
                $this->error("No space_id found anywhere");
                continue;
            }

            $targetMessage = TargetMessage::find($broadcast->target_id);
            if (!$targetMessage) {
                $this->error("TargetMessage not found");
                continue;
            }

            $customers = $targetMessage->customers();

            if (!$customers instanceof Collection || $customers->isEmpty()) {
                $this->info('No customers');
                $this->reschedule($broadcast);
                continue;
            }

            foreach ($customers as $customer) {

                if (empty($customer->whatsapp_number)) {
                    continue;
                }

                MessageService::send(
                    $customer->whatsapp_number,
                    $broadcast->content,
                    (int) $spaceId
                );
            }

            $this->reschedule($broadcast);
        }

        $this->info('Broadcast cron finished');
        return Command::SUCCESS;
    }

    private function reschedule(BroadcastHeader $broadcast): void
    {
        switch ($broadcast->frequency) {
            case 'daily': $next = now()->addDay(); break;
            case 'weekly': $next = now()->addWeek(); break;
            case 'monthly': $next = now()->addMonth(); break;
            case 'yearly': $next = now()->addYear(); break;
            default: return;
        }

        $broadcast->update(['scheduled_at' => $next]);
    }
}
