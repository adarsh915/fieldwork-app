<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_type',
        'recipient_user_id',
        'title',
        'body',
        'screen',
        'total_recipients',
        'devices_reached',
        'status',
        'error_message',
    ];

    protected $casts = [
        'total_recipients' => 'integer',
        'devices_reached'  => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function getRecipientLabelAttribute(): string
    {
        if ($this->recipient_type === 'all') {
            return '📢 All Active Users';
        }
        if ($this->recipient_type === 'field_workers') {
            return '🏃 All Field Workers';
        }
        return '👤 ' . ($this->recipientUser?->name ?? 'Single Employee');
    }
}
