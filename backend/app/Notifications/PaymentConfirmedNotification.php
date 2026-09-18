<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment)
    {
        $this->payment->loadMissing(['party', 'confirmedBy']);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $partyName = $this->payment->party?->name ?? 'Dealer';
        $amount = number_format((float) $this->payment->amount, 2);

        return [
            'message' => "Your payment collection of ₹{$amount} from {$partyName} ({$this->payment->receipt_no}) has been confirmed.",
            'type' => 'payment.confirmed',
            'payment_id' => $this->payment->id,
            'receipt_no' => $this->payment->receipt_no,
            'amount' => $this->payment->amount,
            'party_id' => $this->payment->party_id,
            'party_name' => $partyName,
            'confirmed_at' => $this->payment->confirmed_at?->toIso8601String(),
        ];
    }
}
