<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public int $daysUntilExpiry
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->subscription->plan;
        $expiryDate = $this->subscription->ends_at?->format('d M Y');
        $isUrgent = $this->daysUntilExpiry <= 3;

        $subject = $isUrgent
            ? "[URGENT] Langganan Ultimate POS Anda berakhir dalam {$this->daysUntilExpiry} hari"
            : "Langganan Ultimate POS Anda akan berakhir dalam {$this->daysUntilExpiry} hari";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo {$notifiable->name},");

        if ($isUrgent) {
            $mail->error();
            $mail->line('**Langganan Anda akan segera berakhir!**');
        }

        $mail->line("Langganan paket **{$plan->name}** Anda akan berakhir pada **{$expiryDate}**.")
            ->line("Sisa waktu: **{$this->daysUntilExpiry} hari**")
            ->line('');

        $mail->line('**Apa yang terjadi setelah langganan berakhir?**')
            ->line('- Akun akan masuk masa tenggang 1 hari')
            ->line('- Setelah masa tenggang, akun akan di-freeze')
            ->line('- Anda tetap bisa login dan melihat data, tapi tidak bisa transaksi')
            ->line('');

        $mail->line('**Perpanjang sekarang untuk:**')
            ->line('- Tidak ada gangguan operasional')
            ->line('- Tetap akses semua fitur')
            ->line('- Menjaga data transaksi Anda')
            ->line('');

        $mail->action('Perpanjang Langganan', url(route('subscription.renew')));

        if ($isUrgent) {
            $mail->line('');
            $mail->line('**Catatan:** Jika langganan berakhir di tengah jam operasional, kasir mungkin tidak bisa melakukan transaksi baru.');
        }

        $mail->salutation('Salam, Tim Ultimate POS');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan->name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->subscription->ends_at?->toISOString(),
        ];
    }
}
