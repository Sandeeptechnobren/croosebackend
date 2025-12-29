<?php

namespace App\Services;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class StripeWebhookHandler
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function handle($event)
    {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->updateTransactionStatus($event->data->object->id, 'succeeded');
                break;

            case 'payment_intent.processing':
                $this->updateTransactionStatus($event->data->object->id, 'processing');
                break;

            case 'payment_intent.payment_failed':
                $this->updateTransactionStatus($event->data->object->id, 'failed');
                break;

            case 'payment_intent.canceled':
                $this->updateTransactionStatus($event->data->object->id, 'canceled');
                break;

            case 'charge.succeeded':
                $this->updateTransactionStatus($event->data->object->payment_intent, 'charge_succeeded');
                break;

            case 'charge.failed':
                $this->updateTransactionStatus($event->data->object->payment_intent, 'charge_failed');
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                $this->updateTransactionStatus($charge->payment_intent, 'refunded', [
                    'refund_id' => $charge->refunds->data[0]->id ?? null,
                    'refunded_amount' => $charge->amount_refunded / 100,
                ]);
                break;

            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->updateSessionStatus($session->id, $session->payment_status);
                break;

            case 'checkout.session.expired':
                $session = $event->data->object;
                $this->updateSessionStatus($session->id, 'expired');
                break;

            default:
                Log::info('Unhandled Stripe event type: ' . $event->type);
        }
    }

    protected function updateTransactionStatus($transactionId, $status, $meta = [])
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();
        if ($transaction) {
            $transaction->transaction_status = $status;

            if (!empty($meta)) {
                $existingMeta = json_decode($transaction->meta ?? '{}', true);
                $transaction->meta = json_encode(array_merge($existingMeta, $meta));
            }

            $transaction->save();
        }
    }

    protected function updateSessionStatus($sessionId, $status)
    {
        $transaction = Transaction::where('stripe_session_id', $sessionId)->first();
        if ($transaction) {
            $transaction->transaction_status = $status;
            $transaction->save();
        }
    }
}
