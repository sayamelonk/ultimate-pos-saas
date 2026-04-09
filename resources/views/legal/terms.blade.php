@extends('layouts.guest')

@section('title', 'Syarat & Ketentuan - Ultimate POS')

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm p-8 md:p-12">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Syarat & Ketentuan</h1>
            <p class="text-gray-500 mb-8">Terakhir diperbarui: {{ now()->format('d F Y') }}</p>

            <div class="prose prose-gray max-w-none">
                <p>Selamat datang di Ultimate POS. Dengan mengakses atau menggunakan layanan kami, Anda menyetujui syarat dan ketentuan berikut.</p>

                <h2>1. Definisi</h2>
                <ul>
                    <li><strong>"Layanan"</strong> mengacu pada platform Ultimate POS, termasuk aplikasi web, aplikasi mobile, dan API.</li>
                    <li><strong>"Pengguna"</strong> adalah individu atau badan usaha yang mendaftar dan menggunakan Layanan.</li>
                    <li><strong>"Akun"</strong> adalah akses terdaftar yang diberikan kepada Pengguna untuk menggunakan Layanan.</li>
                    <li><strong>"Data"</strong> adalah semua informasi yang dimasukkan, diunggah, atau dihasilkan oleh Pengguna melalui Layanan.</li>
                </ul>

                <h2>2. Pendaftaran Akun</h2>
                <ul>
                    <li>Anda harus berusia minimal 18 tahun atau memiliki izin dari wali sah untuk menggunakan Layanan.</li>
                    <li>Informasi yang Anda berikan saat pendaftaran harus akurat dan lengkap.</li>
                    <li>Anda bertanggung jawab menjaga kerahasiaan kredensial akun Anda.</li>
                    <li>Satu alamat email hanya dapat digunakan untuk satu akun.</li>
                </ul>

                <h2>3. Masa Percobaan (Trial)</h2>
                <ul>
                    <li>Pengguna baru mendapatkan masa percobaan gratis selama 14 hari.</li>
                    <li>Selama masa percobaan, Anda mendapat akses penuh ke fitur tier Professional.</li>
                    <li>Setelah masa percobaan berakhir, Anda harus memilih paket berlangganan untuk melanjutkan.</li>
                    <li>Jika tidak berlangganan dalam 1 hari setelah trial berakhir, akun akan dibekukan (freeze).</li>
                </ul>

                <h2>4. Langganan & Pembayaran</h2>
                <ul>
                    <li>Layanan tersedia dalam beberapa paket berlangganan dengan harga yang berbeda.</li>
                    <li>Pembayaran dilakukan di muka untuk periode bulanan atau tahunan.</li>
                    <li>Harga belum termasuk pajak yang berlaku.</li>
                    <li>Pembayaran diproses melalui payment gateway Xendit.</li>
                    <li>Biaya transaksi payment gateway ditanggung oleh Ultimate POS.</li>
                </ul>

                <h2>5. Upgrade & Downgrade</h2>
                <ul>
                    <li><strong>Upgrade:</strong> Dapat dilakukan kapan saja. Anda akan dikenakan biaya prorata untuk sisa periode.</li>
                    <li><strong>Downgrade:</strong> Dilakukan dengan membatalkan langganan saat ini, kemudian berlangganan ulang dengan paket yang lebih rendah setelah periode berakhir.</li>
                </ul>

                <h2>6. Pembatalan & Pengembalian Dana</h2>
                <ul>
                    <li>Anda dapat membatalkan langganan kapan saja.</li>
                    <li>Setelah pembatalan, akun tetap aktif hingga akhir periode yang sudah dibayar.</li>
                    <li><strong>Tidak ada pengembalian dana (refund)</strong> untuk pembayaran yang sudah dilakukan.</li>
                    <li>Kami menyediakan masa percobaan 14 hari gratis untuk evaluasi sebelum berlangganan.</li>
                </ul>

                <h2>7. Pembekuan Akun (Freeze)</h2>
                <ul>
                    <li>Akun akan dibekukan jika langganan tidak diperpanjang dalam 1 hari setelah berakhir.</li>
                    <li>Selama pembekuan, Anda masih dapat mengakses dan melihat data, tetapi tidak dapat membuat transaksi baru.</li>
                    <li>Akun dapat diaktifkan kembali kapan saja dengan berlangganan ulang.</li>
                    <li>Data akan disimpan selama 1 tahun sejak akun dibekukan. Setelah itu, data dapat dihapus permanen.</li>
                </ul>

                <h2>8. Penggunaan yang Dilarang</h2>
                <p>Anda dilarang menggunakan Layanan untuk:</p>
                <ul>
                    <li>Aktivitas ilegal atau melanggar hukum yang berlaku.</li>
                    <li>Menyebarkan malware, virus, atau kode berbahaya.</li>
                    <li>Mencoba mengakses sistem atau data milik pengguna lain.</li>
                    <li>Melakukan tindakan yang dapat membebani atau mengganggu infrastruktur Layanan.</li>
                    <li>Membuat akun palsu atau menyalahgunakan masa percobaan.</li>
                </ul>

                <h2>9. Kepemilikan Data</h2>
                <ul>
                    <li>Anda memiliki hak penuh atas Data yang Anda masukkan ke dalam Layanan.</li>
                    <li>Kami tidak akan menjual atau membagikan Data Anda kepada pihak ketiga untuk tujuan pemasaran.</li>
                    <li>Anda dapat mengekspor Data Anda kapan saja selama akun aktif atau dalam masa pembekuan.</li>
                </ul>

                <h2>10. Ketersediaan Layanan</h2>
                <ul>
                    <li>Kami berusaha menyediakan Layanan 24/7, namun tidak menjamin ketersediaan 100%.</li>
                    <li>Pemeliharaan terjadwal akan diinformasikan sebelumnya jika memungkinkan.</li>
                    <li>Kami tidak bertanggung jawab atas kerugian akibat gangguan layanan di luar kendali kami.</li>
                </ul>

                <h2>11. Batasan Tanggung Jawab</h2>
                <ul>
                    <li>Layanan disediakan "sebagaimana adanya" tanpa jaminan tersurat maupun tersirat.</li>
                    <li>Ultimate POS tidak bertanggung jawab atas kerugian tidak langsung, insidental, atau konsekuensial.</li>
                    <li>Total tanggung jawab kami terbatas pada jumlah yang Anda bayarkan dalam 12 bulan terakhir.</li>
                </ul>

                <h2>12. Perubahan Syarat & Ketentuan</h2>
                <ul>
                    <li>Kami berhak mengubah syarat dan ketentuan ini sewaktu-waktu.</li>
                    <li>Perubahan material akan diberitahukan melalui email atau notifikasi dalam aplikasi.</li>
                    <li>Penggunaan berkelanjutan setelah perubahan dianggap sebagai persetujuan.</li>
                </ul>

                <h2>13. Hukum yang Berlaku</h2>
                <p>Syarat dan ketentuan ini diatur oleh hukum Republik Indonesia. Setiap sengketa akan diselesaikan melalui musyawarah atau melalui pengadilan yang berwenang di Indonesia.</p>

                <h2>14. Kontak</h2>
                <p>Jika Anda memiliki pertanyaan tentang syarat dan ketentuan ini, silakan hubungi kami di:</p>
                <ul>
                    <li>Email: support@ultimatepos.com</li>
                    <li>Website: ultimatepos.com</li>
                </ul>
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
