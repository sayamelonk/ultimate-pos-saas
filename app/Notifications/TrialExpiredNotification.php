<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tenantName
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Trial Anda Sudah Berakhir - {$this->tenantName}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Masa trial gratis **{$this->tenantName}** telah berakhir.")
            ->line('Akun Anda sekarang dalam mode **frozen**. Anda masih bisa:')
            ->line('- Login dan melihat data')
            ->line('- Export laporan')
            ->line('')
            ->line('Namun Anda **tidak bisa**:')
            ->line('- Membuat transaksi baru')
            ->line('- Menambah produk/user/outlet')
            ->line('')
            ->line('Untuk melanjutkan menggunakan semua fitur, silakan pilih paket berlangganan.')
            ->action('Pilih Paket Sekarang', url('/subscription/plans'))
            ->line('Data Anda aman dan akan disimpan selama 1 tahun. Anda bisa reaktivasi kapan saja.')
            ->salutation('Salam hangat, Tim Ultimate POS');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tenant_name' => $this->tenantName,
            'status' => 'trial_expired',
        ];
    }
}
