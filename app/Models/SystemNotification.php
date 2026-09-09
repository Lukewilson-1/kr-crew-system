<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_username',
        'type',
        'title',
        'body',
        'data',
        'read_at',
        'email_delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'email_delivered_at' => 'datetime',
        ];
    }

    public function scopeEmailPending($query)
    {
        return $query->whereNull('email_delivered_at');
    }

    public function emailDelivered(): bool
    {
        return $this->email_delivered_at !== null;
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForRecipient($query, string $username)
    {
        return $query->where('recipient_username', $username);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
