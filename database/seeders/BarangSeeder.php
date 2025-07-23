<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Menghapus data barang lama dan membuat data baru...');
        // DB::table('barangs')->truncate();

        // Data diperbarui dengan material bangunan
        $barangsData = [
            ['nama_barang' => 'Atap Spandek 10kk', 'harga_barang' => 75000, 'jumlah_stok' => 150],
            ['nama_barang' => 'Atap Seng Biasa 8kk', 'harga_barang' => 55000, 'jumlah_stok' => 200],
            ['nama_barang' => 'Atap Soka Merah', 'harga_barang' => 7000, 'jumlah_stok' => 500],
            ['nama_barang' => 'Semen Tonasa', 'harga_barang' => 58000, 'jumlah_stok' => 140],
            ['nama_barang' => 'Besi Beton 6mm', 'harga_barang' => 35000, 'jumlah_stok' => 300],
            ['nama_barang' => 'Tripleks 3mm', 'harga_barang' => 50000, 'jumlah_stok' => 180],
            ['nama_barang' => 'Pipa Kuat 1/2 inch', 'harga_barang' => 25000, 'jumlah_stok' => 120],
            ['nama_barang' => 'Pipa Wavin 1/2 inch', 'harga_barang' => 35000, 'jumlah_stok' => 120],
            ['nama_barang' => 'Kran Air 1/2 inch', 'harga_barang' => 20000, 'jumlah_stok' => 150],
            ['nama_barang' => 'Kloset Jongkok Ina', 'harga_barang' => 150000, 'jumlah_stok' => 140],
            ['nama_barang' => 'Balon Lampu Philips 5watt', 'harga_barang' => 22000, 'jumlah_stok' => 250],
            ['nama_barang' => 'Stop Kontak Tunggal', 'harga_barang' => 12000, 'jumlah_stok' => 180],
            ['nama_barang' => 'Saklar Lampu Tunggal', 'harga_barang' => 11000, 'jumlah_stok' => 180],
            ['nama_barang' => 'Papan Colokan 2 lubang', 'harga_barang' => 18000, 'jumlah_stok' => 130],
            ['nama_barang' => 'Kuas Prima 2 inch', 'harga_barang' => 8000, 'jumlah_stok' => 200],
            ['nama_barang' => 'Gagang Pintu Flaco', 'harga_barang' => 85000, 'jumlah_stok' => 160],
            ['nama_barang' => 'Lem Fox 400g', 'harga_barang' => 25000, 'jumlah_stok' => 190],
            ['nama_barang' => 'Lakban Hitam', 'harga_barang' => 10000, 'jumlah_stok' => 150],
            ['nama_barang' => 'Lakban Bening', 'harga_barang' => 10000, 'jumlah_stok' => 150],
            ['nama_barang' => 'Lakban Kertas', 'harga_barang' => 8000, 'jumlah_stok' => 150],
        ];

        foreach ($barangsData as $data) {
            Barang::create($data);
        }

        $this->command->info('Data barang material bangunan berhasil dibuat. 🏗️');
    }
}
