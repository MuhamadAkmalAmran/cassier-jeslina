<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Membuat data transaksi historis...');
        $barangs = Barang::all();
        if ($barangs->isEmpty()) {
            $this->command->error('Tidak ada data barang. Jalankan BarangSeeder dahulu.');
            return;
        }

        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subMonths(6);
        $kasirId = 2;

        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $transactionsPerDay = rand(1, 3);
            for ($i = 0; $i < $transactionsPerDay; $i++) {
                $transaksi = Transaksi::create([
                    'user_id' => $kasirId, 'total_harga_barang' => 0, 'created_at' => $date, 'updated_at' => $date,
                ]);

                $itemsPerTransaction = rand(1, 2);
                $itemsToAttach = [];
                $totalHarga = 0;

                for ($j = 0; $j < $itemsPerTransaction; $j++) {
                    $selectedBarang = $barangs->random();

                    // Logika jumlah beli disederhanakan karena tidak ada kategori
                    $jumlahBeli = rand(1, 2);

                    if ($selectedBarang->jumlah_stok >= $jumlahBeli && !isset($itemsToAttach[$selectedBarang->id])) {
                        $selectedBarang->decrement('jumlah_stok', $jumlahBeli);
                        $itemsToAttach[$selectedBarang->id] = [
                            'jumlah' => $jumlahBeli, 'harga_satuan' => $selectedBarang->harga_barang, 'created_at' => $date, 'updated_at' => $date,
                        ];
                        $totalHarga += $jumlahBeli * $selectedBarang->harga_barang;
                    }
                }

                if (!empty($itemsToAttach)) {
                    $transaksi->update(['total_harga_barang' => $totalHarga]);
                    $transaksi->barangs()->attach($itemsToAttach);
                    $dibayar = $totalHarga + rand(0, 50000);
                    $transaksi->pembayaran()->create([
                        'total_pembayaran' => $totalHarga, 'dibayar' => $dibayar, 'kembalian' => $dibayar - $totalHarga,
                    ]);
                } else {
                    $transaksi->delete();
                }
            }
        }
        $this->command->info('Data transaksi sederhana berhasil dibuat dan stok diperbarui. 🛠️');
    }
}
