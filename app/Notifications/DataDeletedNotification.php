<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct() {}

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
        return (new MailMessage)
            ->subject('Data Akun Ultimate POS Anda Telah Dihapus')
            ->greeting('Halo,')
            ->line('Sesuai dengan pemberitahuan sebelumnya, data akun Ultimate POS Anda telah dihapus karena akun telah dalam status frozen selama lebih dari 1 tahun.')
            ->line('')
            ->line('**Data yang telah dihapus:**')
            ->line('- Semua data transaksi')
            ->line('- Data produk dan inventory')
            ->line('- Data pelanggan')
            ->line('- Laporan dan riwayat')
            ->line('- Akun user dan outlet')
            ->line('')
            ->line('Penghapusan ini bersifat permanen dan data tidak dapat dipulihkan.')
            ->line('')
            ->line('Jika Anda ingin menggunakan Ultimate POS lagi di masa depan, Anda dapat mendaftar ulang dengan akun baru.')
            ->action('Daftar Ulang', url(route('register')))
            ->line('')
            ->line('Terima kasih telah menggunakan Ultimate POS.')
            ->salutation('Salam, Tim Ultimate POS');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => 'data_deleted',
            'deleted_at' => now()->toISOString(),
        ];
    }
}
