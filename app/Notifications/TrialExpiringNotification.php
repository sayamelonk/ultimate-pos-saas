<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $daysRemaining,
        public string $tenantName,
        public string $trialEndsAt
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->daysRemaining) {
            7 => "Trial Anda Tersisa 7 Hari - {$this->tenantName}",
            3 => "Trial Anda Tersisa 3 Hari - {$this->tenantName}",
            1 => "Trial Anda Berakhir Besok - {$this->tenantName}",
            default => "Reminder: Trial Anda Akan Berakhir - {$this->tenantName}",
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo {$notifiable->name}!")
            ->line($this->getMainMessage());

        if ($this->daysRemaining <= 3) {
            $message->line('Jangan sampai kehilangan akses ke data dan fitur Anda. Pilih paket berlangganan sekarang untuk melanjutkan.');
        }

        return $message
            ->line("**Trial berakhir:** {$this->trialEndsAt}")
            ->action('Pilih Paket Sekarang', url('/subscription/plans'))
            ->line('Terima kasih telah menggunakan Ultimate POS!')
            ->salutation('Salam hangat, Tim Ultimate POS');
    }

    protected function getMainMessage(): string
    {
        return match ($this->daysRemaining) {
            7 => "Masa trial gratis **{$this->tenantName}** tersisa **7 hari**. Nikmati semua fitur Professional selama trial berlangsung.",
            3 => "Masa trial **{$this->tenantName}** akan berakhir dalam **3 hari**. Pastikan Anda sudah menyiapkan paket berlangganan.",
            1 => "Masa trial **{$this->tenantName}** akan berakhir **besok**! Segera pilih paket untuk melanjutkan menggunakan sistem.",
            default => "Masa trial **{$this->tenantName}** akan segera berakhir.",
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'days_remaining' => $this->daysRemaining,
            'tenant_name' => $this->tenantName,
            'trial_ends_at' => $this->trialEndsAt,
        ];
    }
}
