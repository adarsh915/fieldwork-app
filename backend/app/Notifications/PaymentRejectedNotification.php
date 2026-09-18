<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment)
    {
        $this->payment->loadMissing(['party']);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $partyName = $this->payment->party?->name ?? 'Dealer';
        $amount = number_format((float) $this->payment->amount, 2);
        $reason = $this->payment->rejection_reason ?? 'Verification failed';

        return [
            'message' => "Payment collection of ₹{$amount} from {$partyName} was rejected. Reason: {$reason}",
            'type' => 'payment.rejected',
            'payment_id' => $this->payment->id,
            'receipt_no' => $this->payment->receipt_no,
            'amount' => $this->payment->amount,
            'party_id' => $this->payment->party_id,
            'party_name' => $partyName,
            'rejection_reason' => $reason,
        ];
    }
}
