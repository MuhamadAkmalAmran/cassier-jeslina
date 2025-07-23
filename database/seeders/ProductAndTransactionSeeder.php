<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductAndTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Membuat data barang...');
        $barangsData = [
            ['nama' => 'Kopi Instan Sachet', 'harga' => 2500, 'stok' => 500, 'musim' => 'netral'],
            ['nama' => 'Teh Celup Kotak', 'harga' => 8000, 'stok' => 300, 'musim' => 'netral'],
            ['nama' => 'Mie Instan Goreng', 'harga' => 3500, 'stok' => 800, 'musim' => 'hujan'],
            ['nama' => 'Biskuit Coklat', 'harga' => 12000, 'stok' => 400, 'musim' => 'netral'],
            ['nama' => 'Minuman Soda Dingin', 'harga' => 7000, 'stok' => 600, 'musim' => 'kemarau'],
            ['nama' => 'Coklat Batang', 'harga' => 15000, 'stok' => 250, 'musim' => 'netral'],
            ['nama' => 'Sabun Mandi Cair', 'harga' => 22000, 'stok' => 150, 'musim' => 'netral'],
            ['nama' => 'Shampo Botol', 'harga' => 18000, 'stok' => 180, 'musim' => 'netral'],
            ['nama' => 'Jas Hujan Ponco', 'harga' => 25000, 'stok' => 100, 'musim' => 'hujan'],
            ['nama' => 'Es Krim Cup', 'harga' => 10000, 'stok' => 200, 'musim' => 'kemarau'],
        ];

        $createdBarangs = [];
        foreach ($barangsData as $barang) {
            $createdBarangs[] = Barang::create([
                'nama_barang' => $barang['nama'],
                'harga_barang' => $barang['harga'],
                'jumlah_stok' => $barang['stok'],
            ]);
        }
        $this->command->info('Data barang berhasil dibuat.');

        $this->command->info('Membuat data transaksi historis...');
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subMonths(12);
        $kasirId = 2;

        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $transactionsPerDay = rand(5, 15);

            for ($i = 0; $i < $transactionsPerDay; $i++) {
                $transaksi = Transaksi::create([
                    'user_id' => $kasirId,
                    'total_harga_barang' => 0,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                $itemsPerTransaction = rand(1, 4);
                $itemsToAttach = [];
                $totalHarga = 0;

                for ($j = 0; $j < $itemsPerTransaction; $j++) {
                    $barangIndex = rand(0, count($createdBarangs) - 1);
                    $selectedBarang = $createdBarangs[$barangIndex];
                    $barangInfo = $barangsData[$barangIndex];
                    $jumlahBeli = rand(1, 5);

                    $currentMonth = $date->month;
                    if ($barangInfo['musim'] === 'kemarau' && in_array($currentMonth, [4, 5, 6, 7, 8, 9])) {
                        $jumlahBeli = rand(5, 15);
                    }
                    if ($barangInfo['musim'] === 'hujan' && in_array($currentMonth, [10, 11, 12, 1, 2, 3])) {
                        $jumlahBeli = rand(5, 15);
                    }

                    if (!isset($itemsToAttach[$selectedBarang->id])) {
                        // --- PERUBAHAN UTAMA DI SINI ---
                        $itemsToAttach[$selectedBarang->id] = [
                            'jumlah' => $jumlahBeli,
                            'harga_satuan' => $selectedBarang->harga_barang,
                            // TAMBAHKAN DUA BARIS INI UNTUK MENYIMPAN TANGGAL HISTORIS
                            'created_at' => $date,
                            'updated_at' => $date,
                        ];
                        // --- AKHIR PERUBAHAN ---
                        $totalHarga += $jumlahBeli * $selectedBarang->harga_barang;
                    }
                }

                if (!empty($itemsToAttach)) {
                    $transaksi->update(['total_harga_barang' => $totalHarga]);
                    $transaksi->barangs()->attach($itemsToAttach);

                    $dibayar = $totalHarga + rand(0, 10000);
                    $transaksi->pembayaran()->create([
                        'total_pembayaran' => $totalHarga,
                        'dibayar' => $dibayar,
                        'kembalian' => $dibayar - $totalHarga,
                    ]);
                }
            }
        }
        $this->command->info('Data transaksi historis berhasil dibuat.');
    }
}
