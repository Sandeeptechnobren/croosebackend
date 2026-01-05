<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BroadcastSchedule;
use App\Models\Customer;

class SendBroadcast extends Command
{
    protected $signature = 'broadcast:send';
    protected $description = 'Send scheduled broadcast messages';

    public function handle()
    {
        $schedules = BroadcastSchedule::where('status','pending')
            ->where('scheduled_at','<=',now())
            ->get();

        foreach ($schedules as $schedule) {

            $broadcast = $schedule->broadcast;
            $target = $broadcast->target;

            $customers = match ($target->target_type) {
                'new' => Customer::where('status','new')->get(),
                'active' => Customer::where('status','active')->get(),
                'recent' => Customer::whereDate('created_at','>=',now()->subDays(7))->get(),
                default => Customer::all(),
            };

            foreach ($customers as $customer) {
                // 👇 yahin actual SMS / WhatsApp / Email send logic aayega
                // example:
                // WhatsAppService::send($customer->phone, $broadcast->message);
            }

            $schedule->update(['status' => 'sent']);
        }

        return Command::SUCCESS;
    }
}
