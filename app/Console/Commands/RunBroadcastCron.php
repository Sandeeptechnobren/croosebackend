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
        $broadcasts = BroadcastHeader::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($broadcasts as $broadcast) {

            // Idempotency: never re-send an occurrence we already delivered. A recurring
            // broadcast advances scheduled_at into the future after sending, so its NEXT
            // occurrence again has last_sent_at < scheduled_at and will fire on schedule.
            // (This is what stops a past-due 'once' broadcast from blasting every minute.)
            if ($broadcast->last_sent_at && $broadcast->scheduled_at
                && $broadcast->last_sent_at->gte($broadcast->scheduled_at)) {
                continue;
            }

            $this->info("Processing Broadcast ID: {$broadcast->id}");

            // broadcast_headers has no space_id column, so fall back to the first instance.
            $spaceId = $broadcast->space_id ?: Space_whapichannel_details::value('space_id');
            if (!$spaceId) {
                $this->error("Broadcast {$broadcast->id}: no space_id found");
                continue;
            }

            $targetMessage = TargetMessage::find($broadcast->target_id);
            if ($targetMessage) {
                $customers = $targetMessage->customers();
                if ($customers instanceof Collection) {
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
                }
            }

            // Mark this occurrence delivered, then advance (recurring) or disable (once).
            $broadcast->last_sent_at = now();
            $broadcast->save();
            $this->reschedule($broadcast);
        }

        return Command::SUCCESS;
    }

    private function reschedule(BroadcastHeader $broadcast): void
    {
        switch ($broadcast->frequency) {
            case 'daily':   $next = now()->addDay();   break;
            case 'weekly':  $next = now()->addWeek();  break;
            case 'monthly': $next = now()->addMonth(); break;
            case 'yearly':  $next = now()->addYear();  break;
            default:
                // 'once' (or unknown frequency): deliver a single time, then stop firing.
                $broadcast->update(['scheduled_at' => null]);
                return;
        }

        $broadcast->update(['scheduled_at' => $next]);
    }
}
