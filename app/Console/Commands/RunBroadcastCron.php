<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BroadcastHeader;
use App\Models\TargetMessage;
use App\Services\MessageService;
use Illuminate\Support\Collection;

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
        $this->info('Total broadcasts: ' . $broadcasts->count());
        foreach ($broadcasts as $broadcast) {
            $this->info("Processing Broadcast ID: {$broadcast->id}");

            // 1. Target message
            $targetMessage = TargetMessage::find($broadcast->target_id);
            if (!$targetMessage) {
                $this->error("TargetMessage not found (ID {$broadcast->target_id})");
                continue;
            }

            // 2. Customers
            $customers = $targetMessage->customers();
            if (!$customers instanceof Collection) {
                $this->error('TargetMessage::customers() must return Collection');
                continue;
            }
            if ($customers->isEmpty()) {
                $this->info('No customers found');
                $this->reschedule($broadcast);
                continue;
            }
            $this->info('Customers found: ' . $customers->count());

            // 3. Send messages
            foreach ($customers as $customer) {
                if (empty($customer->whatsapp_number)) {
                    $this->error("Customer {$customer->id} has no whatsapp_number");
                    continue;
                }
                $sent = MessageService::send(
                    $customer->whatsapp_number,
                    $broadcast->content
                );
                if ($sent) {
                    $this->info("SMS sent → {$customer->whatsapp_number}");
                } else {
                    $this->error("SMS failed → {$customer->whatsapp_number}");
                }
            }

            // 4. Reschedule based on frequency
            $this->reschedule($broadcast);
            $this->info("Broadcast {$broadcast->id} completed");
        }
        $this->info('Broadcast cron finished');
        return Command::SUCCESS;
    }
    private function reschedule(BroadcastHeader $broadcast): void
    {
        $nextSchedule = null;
        switch ($broadcast->frequency) {
            case 'daily':
                $nextSchedule = now()->addDay();
                break;
            case 'weekly':
                $nextSchedule = now()->addWeek();
                break;
            case 'monthly':
                $nextSchedule = now()->addMonth();
                break;
            case 'yearly':
                $nextSchedule = now()->addYear();
                break;
            default:
                // one-time broadcast
                $this->info("One-time broadcast, no reschedule");
                return;
        }
        $broadcast->update([
            'scheduled_at' => $nextSchedule,
        ]);
        $this->info("Next schedule set to {$nextSchedule}");
    }
}
