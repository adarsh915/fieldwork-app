<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment)
    {
        $this->payment->loadMissing(['party', 'collectedBy']);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $repName = $this->payment->collectedBy?->name ?? 'Field Rep';
        $partyName = $this->payment->party?->name ?? 'Dealer';
        $amount = number_format((float) $this->payment->amount, 2);

        return [
            'message' => "{$repName} recorded a payment of ₹{$amount} from {$partyName} — pending your confirmation.",
            'type' => 'payment.recorded',
            'payment_id' => $this->payment->id,
            'receipt_no' => $this->payment->receipt_no,
            'amount' => $this->payment->amount,
            'party_id' => $this->payment->party_id,
            'party_name' => $partyName,
            'collected_by' => $this->payment->collected_by,
            'rep_name' => $repName,
            'payment_mode' => $this->payment->payment_mode,
        ];
    }
}
