<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The payments ledger: every payment and refund on an order, and the order's paid amount + payment status worked
 * out from it (never set by hand).
 */
class PaymentService
{
    /**
     * @param  array{trx_id?: ?string, note?: ?string, is_refund?: bool, status?: TransactionStatus, payload?: ?array, paid_at?: mixed}  $details
     */
    public function record(Order $order, PaymentMethod $method, int $amount, array $details = [], ?User $by = null): Payment
    {
        if ($amount < 1) {
            throw new \InvalidArgumentException('A payment needs an amount.');
        }

        return DB::transaction(function () use ($order, $method, $amount, $details, $by) {
            $payment = $order->payments()->create([
                'method' => $method,
                'amount' => $amount,
                'is_refund' => $details['is_refund'] ?? false,
                'status' => $details['status'] ?? TransactionStatus::Completed,
                'trx_id' => filled($details['trx_id'] ?? null) ? trim($details['trx_id']) : null,
                'note' => $details['note'] ?? null,
                'payload' => $details['payload'] ?? null,
                'paid_at' => $details['paid_at'] ?? now(),
                'user_id' => $by?->id,
            ]);

            $this->refresh($order);

            if ($payment->status === TransactionStatus::Completed) {
                $order->history()->create([
                    'from_status' => $order->status,
                    'to_status' => $order->status,
                    'note' => ($payment->is_refund ? 'Refund' : 'Payment received').': BDT '.number_format($amount).' by '.$method->getLabel()
                        .($payment->trx_id ? " (trx {$payment->trx_id})" : '').'.',
                    'user_id' => $by?->id,
                ]);
                $order->unsetRelation('history');
            }

            return $payment;
        });
    }

    /** Re-work the order's paid amount and payment status from its completed payments. */
    public function refresh(Order $order): void
    {
        $completed = $order->payments()->where('status', TransactionStatus::Completed->value)->get();
        $paid = (int) $completed->sum(fn (Payment $payment) => $payment->signedAmount());

        $order->forceFill([
            'paid_amount' => $paid,
            'payment_status' => self::statusFor($paid, $order->total, $completed->contains('is_refund', true)),
        ])->save();

        $order->unsetRelation('payments');
    }

    public static function statusFor(int $paid, int $total, bool $hasRefunds = false): PaymentStatus
    {
        return match (true) {
            $paid <= 0 => $hasRefunds ? PaymentStatus::Refunded : PaymentStatus::Unpaid,
            $paid >= $total => PaymentStatus::Paid,
            default => PaymentStatus::Partial,
        };
    }
}
