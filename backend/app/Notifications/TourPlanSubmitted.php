<?php

namespace App\Notifications;

use App\Models\DayStart;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TourPlanSubmitted extends Notification
{
    use Queueable;

    public function __construct(public DayStart $dayStart)
    {
        $this->dayStart->loadMissing(['user', 'tourPurpose']);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $employeeName = $this->dayStart->user->name;
        $date = $this->dayStart->date->format('d M Y');
        $purpose = $this->dayStart->tourPurpose->name;

        return [
            'message' => "{$employeeName} submitted a tour plan for {$date} — {$purpose}",
            'day_start_id' => $this->dayStart->id,
            'employee_name' => $employeeName,
            'date' => $this->dayStart->date->toDateString(),
            'tour_purpose' => $purpose,
        ];
    }
}
