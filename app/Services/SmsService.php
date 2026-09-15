<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send a short SMS message. The default 'log' provider is a stub that only
     * records the would-be message, so the rest of the app (T-1hr departure
     * notices) keeps working before a real gateway is configured. Best-effort:
     * a delivery failure is logged and never throws.
     */
    public function send(string $mobile, string $message): bool
    {
        $mobile = $this->normalize($mobile);
        if ($mobile === '') {
            return false;
        }

        if (! $this->enabled() || config('sms.provider', 'log') === 'log') {
            Log::debug('SMS (log provider stub)', [
                'to' => $mobile,
                'from' => config('sms.from', 'KR-Crew'),
                'message' => $message,
            ]);

            return true;
        }

        try {
            $sent = $this->sendViaProvider($mobile, $message);

            return $sent;
        } catch (\Throwable $e) {
            Log::error('SMS send failed', [
                'to' => $mobile,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Convenience used by the T-1hr departure path and crew:send-departure-sms.
     * Message mirrors the register example: "PT-01 departs KBX01 06:30 — be ready".
     */
    public function sendDepartureReminder(string $mobile, string $serviceId, ?string $depot = null, ?string $departureTime = null): bool
    {
        $parts = array_values(array_filter([
            $depot !== null ? trim($depot) : '',
            $departureTime !== null ? trim($departureTime) : '',
        ]));

        $message = trim($serviceId.' departs '.implode(' ', $parts).' — be ready');

        return $this->send($mobile, $message);
    }

    /**
     * Provider hook. Extend with a real gateway (e.g. Africa's Talking / Twilio)
     * by switching on config('sms.provider'). The signature is intentionally
     * small so the rest of the system does not change when a gateway lands.
     */
    protected function sendViaProvider(string $mobile, string $message): bool
    {
        Log::debug('SMS provider not implemented yet; nothing sent.', [
            'to' => $mobile,
            'message' => $message,
        ]);

        return false;
    }

    protected function enabled(): bool
    {
        return (bool) config('sms.enabled', false);
    }

    protected function normalize(string $mobile): string
    {
        $mobile = preg_replace('/[^0-9+]/', '', trim($mobile));

        return is_string($mobile) ? $mobile : '';
    }
}