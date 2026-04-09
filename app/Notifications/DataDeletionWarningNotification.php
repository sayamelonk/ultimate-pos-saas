<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataDeletionWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $daysUntilDeletion,
        public string $warningType = 'first'
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
        $isUrgent = $this->warningType === 'second';
        $subject = $isUrgent
            ? "[URGENT] Data Anda akan dihapus dalam {$this->daysUntilDeletion} hari"
            : "Peringatan: Data Anda akan dihapus dalam {$this->daysUntilDeletion} hari";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo {$notifiable->name},");

        if ($isUrgent) {
            $mail->error();
            $mail->line('**PERINGATAN PENTING**: Akun Ultimate POS Anda telah dalam status frozen selama hampir 1 tahun.');
            $mail->line("Data bisnis Anda akan **DIHAPUS PERMANEN** dalam **{$this->daysUntilDeletion} hari**.");
        } else {
            $mail->line('Akun Ultimate POS Anda telah dalam status frozen selama beberapa waktu.');
            $mail->line('Sesuai kebijakan kami, data yang tidak aktif selama 1 tahun akan dihapus secara permanen.');
            $mail->line("Data Anda dijadwalkan untuk dihapus dalam **{$this->daysUntilDeletion} hari**.");
        }

        $mail->line('')
            ->line('**Apa yang akan dihapus:**')
            ->line('- Semua data transaksi')
            ->line('- Data produk dan inventory')
            ->line('- Data pelanggan')
            ->line('- Laporan dan riwayat')
            ->line('- Akun user dan outlet')
            ->line('');

        $mail->line('**Untuk mempertahankan data Anda:**')
            ->action('Reaktivasi Akun Sekarang', url(route('subscription.choose-plan')))
            ->line('');

        if ($isUrgent) {
            $mail->line('Jika Anda ingin mengekspor data sebelum dihapus, silakan login dan gunakan fitur export di menu Laporan.');
        }

        $mail->line('Jika Anda tidak lagi membutuhkan data ini, Anda dapat mengabaikan email ini.')
            ->salutation('Salam, Tim Ultimate POS');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'days_until_deletion' => $this->daysUntilDeletion,
            'warning_type' => $this->warningType,
        ];
    }
}
