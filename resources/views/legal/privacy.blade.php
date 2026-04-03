@extends('layouts.guest')

@section('title', 'Kebijakan Privasi - Ultimate POS')

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm p-8 md:p-12">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Kebijakan Privasi</h1>
            <p class="text-gray-500 mb-8">Terakhir diperbarui: {{ now()->format('d F Y') }}</p>

            <div class="prose prose-gray max-w-none">
                <p>Ultimate POS ("kami") berkomitmen melindungi privasi Anda. Kebijakan ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi Anda.</p>

                <h2>1. Informasi yang Kami Kumpulkan</h2>

                <h3>1.1 Informasi yang Anda Berikan</h3>
                <ul>
                    <li><strong>Informasi Akun:</strong> Nama, alamat email, nomor telepon, nama bisnis saat pendaftaran.</li>
                    <li><strong>Informasi Pembayaran:</strong> Data yang diperlukan untuk memproses pembayaran melalui payment gateway.</li>
                    <li><strong>Data Bisnis:</strong> Produk, transaksi penjualan, data pelanggan, inventaris, dan data operasional lainnya yang Anda masukkan ke dalam sistem.</li>
                </ul>

                <h3>1.2 Informasi yang Dikumpulkan Otomatis</h3>
                <ul>
                    <li><strong>Data Penggunaan:</strong> Halaman yang dikunjungi, fitur yang digunakan, waktu akses.</li>
                    <li><strong>Informasi Perangkat:</strong> Jenis browser, sistem operasi, alamat IP, jenis perangkat.</li>
                    <li><strong>Cookies:</strong> Untuk menjaga sesi login dan preferensi pengguna.</li>
                </ul>

                <h2>2. Bagaimana Kami Menggunakan Informasi</h2>
                <p>Kami menggunakan informasi Anda untuk:</p>
                <ul>
                    <li>Menyediakan dan mengoperasikan Layanan.</li>
                    <li>Memproses pembayaran dan mengelola langganan.</li>
                    <li>Mengirim notifikasi penting terkait akun dan layanan.</li>
                    <li>Memberikan dukungan pelanggan.</li>
                    <li>Meningkatkan dan mengembangkan fitur Layanan.</li>
                    <li>Mencegah penipuan dan menjaga keamanan.</li>
                    <li>Memenuhi kewajiban hukum.</li>
                </ul>

                <h2>3. Bagaimana Kami Melindungi Informasi</h2>
                <ul>
                    <li><strong>Enkripsi:</strong> Data ditransmisikan melalui koneksi terenkripsi (HTTPS/SSL).</li>
                    <li><strong>Akses Terbatas:</strong> Hanya personel yang berwenang yang dapat mengakses data.</li>
                    <li><strong>Backup:</strong> Data di-backup secara otomatis setiap hari untuk mencegah kehilangan data.</li>
                    <li><strong>Isolasi Data:</strong> Data setiap tenant/bisnis terpisah dan tidak dapat diakses oleh pengguna lain.</li>
                </ul>

                <h2>4. Berbagi Informasi dengan Pihak Ketiga</h2>
                <p>Kami <strong>TIDAK</strong> menjual data Anda. Kami hanya membagikan informasi kepada:</p>
                <ul>
                    <li><strong>Payment Gateway (Xendit):</strong> Untuk memproses pembayaran.</li>
                    <li><strong>Penyedia Hosting:</strong> Untuk menyimpan dan mengoperasikan Layanan.</li>
                    <li><strong>Otoritas Hukum:</strong> Jika diwajibkan oleh hukum atau proses hukum yang sah.</li>
                </ul>

                <h2>5. Penyimpanan & Retensi Data</h2>
                <ul>
                    <li>Data Anda disimpan di server yang berlokasi di Indonesia.</li>
                    <li>Selama akun aktif, data Anda disimpan tanpa batas waktu.</li>
                    <li>Jika akun dibekukan (freeze), data disimpan selama 1 tahun.</li>
                    <li>Setelah 1 tahun tidak aktif, data dapat dihapus permanen dengan pemberitahuan sebelumnya.</li>
                    <li>Anda dapat meminta penghapusan data kapan saja dengan menghubungi kami.</li>
                </ul>

                <h2>6. Hak Anda</h2>
                <p>Anda memiliki hak untuk:</p>
                <ul>
                    <li><strong>Mengakses:</strong> Melihat data pribadi yang kami simpan tentang Anda.</li>
                    <li><strong>Memperbaiki:</strong> Memperbarui informasi yang tidak akurat.</li>
                    <li><strong>Mengekspor:</strong> Mengunduh data Anda dalam format yang dapat dibaca.</li>
                    <li><strong>Menghapus:</strong> Meminta penghapusan data Anda (dengan konsekuensi penutupan akun).</li>
                    <li><strong>Menolak:</strong> Menolak penggunaan data untuk tujuan tertentu.</li>
                </ul>

                <h2>7. Cookies</h2>
                <p>Kami menggunakan cookies untuk:</p>
                <ul>
                    <li>Menjaga sesi login Anda tetap aktif.</li>
                    <li>Menyimpan preferensi pengguna.</li>
                    <li>Menganalisis penggunaan layanan untuk perbaikan.</li>
                </ul>
                <p>Anda dapat mengatur browser untuk menolak cookies, namun beberapa fitur mungkin tidak berfungsi dengan baik.</p>

                <h2>8. Layanan Pihak Ketiga</h2>
                <p>Layanan kami dapat terintegrasi dengan layanan pihak ketiga. Kami tidak bertanggung jawab atas praktik privasi layanan tersebut. Harap tinjau kebijakan privasi masing-masing layanan.</p>

                <h2>9. Keamanan Anak-anak</h2>
                <p>Layanan kami tidak ditujukan untuk anak di bawah 18 tahun. Kami tidak secara sengaja mengumpulkan informasi dari anak-anak.</p>

                <h2>10. Perubahan Kebijakan Privasi</h2>
                <ul>
                    <li>Kami dapat memperbarui kebijakan ini dari waktu ke waktu.</li>
                    <li>Perubahan signifikan akan diberitahukan melalui email atau notifikasi dalam aplikasi.</li>
                    <li>Tanggal "terakhir diperbarui" di bagian atas akan menunjukkan versi terbaru.</li>
                </ul>

                <h2>11. Kontak</h2>
                <p>Untuk pertanyaan tentang kebijakan privasi atau untuk menggunakan hak Anda, hubungi kami di:</p>
                <ul>
                    <li>Email: privacy@ultimatepos.com</li>
                    <li>Website: ultimatepos.com</li>
                </ul>

                <h2>12. Persetujuan</h2>
                <p>Dengan menggunakan Layanan kami, Anda menyetujui pengumpulan dan penggunaan informasi sesuai dengan kebijakan privasi ini.</p>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <a href="{{ url('/') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    &larr; Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
