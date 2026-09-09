<?php

namespace App\Console\Commands;

use App\Mail\SystemNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'notifications:test-email
                            {email? : Recipient address. Defaults to the first ALERT_EMAIL_RECIPIENTS entry.}';

    protected $description = 'Send a test email to verify SMTP/notification configuration';

    public function handle(): int
    {
        $address = $this->argument('email')
            ?? (config('notifications.email.alert_recipients')[0] ?? null);

        if (blank($address)) {
            $this->error('No recipient given and no ALERT_EMAIL_RECIPIENTS configured.');

            return self::FAILURE;
        }

        $mailer = config('mail.default', 'smtp');
        $this->line(sprintf('Using mailer "%s" to deliver to %s', $mailer, $address));

        try {
            Mail::to($address)->send(new SystemNotificationMail(
                'KR Crew System — test email',
                'This is a test email from the KR Crew & Running-Room Management System. If you received it, email notifications are configured correctly.',
                'test',
                ['source' => 'notifications:test-email'],
                'ICT Test',
            ));
        } catch (\Throwable $e) {
            $this->error('Delivery failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Test email queued/sent to %s.', $address));

        return self::SUCCESS;
    }
}