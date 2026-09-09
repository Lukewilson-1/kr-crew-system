<?php

namespace App\Services;

use App\Mail\SystemNotificationMail;
use App\Models\SystemNotification;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Persist an in-app notification for a single recipient and optionally
     * deliver an email copy to the recipient's registered email address.
     *
     * Email delivery is controlled by NOTIFICATIONS_EMAIL_ENABLED and is
     * best-effort: a delivery failure is logged and never prevents the
     * in-app notification from being recorded.
     */
    public function notify(
        string $recipientUsername,
        string $title,
        string $body,
        ?string $type = null,
        array $data = [],
    ): ?SystemNotification {
        $notification = SystemNotification::create([
            'recipient_username' => $recipientUsername,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'read_at' => null,
        ]);

        $user = User::query()->whereKey($recipientUsername)->first();
        if (! $user) {
            return $notification;
        }

        $this->deliverEmail(
            $user->email,
            $title,
            $body,
            $type,
            $data,
            $user->name,
            $notification,
        );

        return $notification;
    }

    /**
     * Persist an in-app notification for many recipients at once.
     */
    public function notifyUsers(
        iterable $recipientUsernames,
        string $title,
        string $body,
        ?string $type = null,
        array $data = [],
    ): int {
        $sent = 0;

        foreach ($recipientUsernames as $username) {
            $this->notify((string) $username, $title, $body, $type, $data);
            $sent++;
        }

        return $sent;
    }

    /**
     * Send an email without persisting an in-app notification (used for
     * alert-channel / operational messages to external addresses).
     */
    public function email(
        string $email,
        string $title,
        string $body,
        ?string $type = null,
        array $data = [],
    ): bool {
        if (! $this->emailEnabled() || blank($email)) {
            return false;
        }

        try {
            $this->queueOrSend($email, new SystemNotificationMail($title, $body, $type, $data));

            return true;
        } catch (\Throwable $e) {
            Log::error('Notification email send failed', [
                'to' => $email,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send an email to the configured operational alert recipients
     * (ALERT_EMAIL_RECIPIENTS). Used for security/operations alerts.
     *
     * @return array<string, bool> map of address => delivery success
     */
    public function emailAdmins(
        string $title,
        string $body,
        ?string $type = 'alert',
        array $data = [],
    ): array {
        $results = [];

        foreach (config('notifications.email.alert_recipients', []) as $address) {
            $results[$address] = $this->email($address, $title, $body, $type, $data);
        }

        if (empty($results)) {
            Log::info('No alert email recipients configured; skipped admin email', [
                'title' => $title,
            ]);
        }

        return $results;
    }

    protected function emailEnabled(): bool
    {
        return (bool) config('notifications.email.enabled', true);
    }

    /**
     * Best-effort email delivery of a persisted in-app notification.
     */
    protected function deliverEmail(
        ?string $email,
        string $title,
        string $body,
        ?string $type,
        array $data,
        ?string $recipientName,
        ?SystemNotification $notification = null,
    ): bool {
        if (! $this->emailEnabled() || blank($email)) {
            return false;
        }

        try {
            $this->queueOrSend(
                $email,
                new SystemNotificationMail($title, $body, $type, $data, $recipientName),
            );

            if ($notification) {
                $notification->forceFill(['email_delivered_at' => now()])->saveQuietly();
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Notification email delivery failed', [
                'to' => $email,
                'title' => $title,
                'notification_id' => $notification?->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Use the queue when a real queue driver is configured, otherwise send
     * synchronously so notifications are delivered even on shared hosting.
     */
    protected function queueOrSend(string $email, SystemNotificationMail $mailable): void
    {
        $queue = config('queue.default', 'sync');

        if ($queue !== null && $queue !== 'sync' && $queue !== 'array') {
            Mail::to($email)->queue($mailable);

            return;
        }

        Mail::to($email)->send($mailable);
    }
}