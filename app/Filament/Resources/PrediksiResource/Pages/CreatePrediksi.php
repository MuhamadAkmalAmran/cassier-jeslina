<?php

namespace App\Filament\Resources\PrediksiResource\Pages;

use App\Filament\Resources\PrediksiResource;
use App\Models\Barang;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePrediksi extends CreateRecord
{
    protected static string $resource = PrediksiResource::class;

    protected function getRedirectUrl(): string
    {
        // Arahkan ke halaman view dari job yang baru dibuat agar langsung lihat hasilnya
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        $prediksiJob = $this->record; // Ini adalah "Job" Prediksi yang baru dibuat
        $barang = Barang::find($prediksiJob->barang_id);
        $periods = (int) $prediksiJob->periode_data; // Misal: 3
        $targetPredictionDate = Carbon::parse($prediksiJob->tanggal_prediksi); // Misal: Agustus 2025

        // Lakukan perulangan untuk setiap bulan dalam periode yang dipilih
        // Contoh: jika target Agustus & periode 3 bulan, loop ini akan berjalan untuk Agustus, Juli, dan Juni
        for ($k = 0; $k < $periods; $k++) {
            // Tentukan bulan yang sedang kita hitung prediksinya
            $currentPredictionMonth = $targetPredictionDate->copy()->subMonths($k);

            // Kumpulkan data historis untuk bulan yang sedang dihitung
            $monthlySales = [];
            for ($i = 1; $i <= $periods; $i++) {
                $historyMonth = $currentPredictionMonth->copy()->subMonths($i);
                $sales = DB::table('barang_transaksi')
                    ->where('barang_id', $barang->id)
                    ->whereBetween('created_at', [$historyMonth->copy()->startOfMonth(), $historyMonth->copy()->endOfMonth()])
                    ->sum('jumlah');
                $monthlySales[] = $sales;
            }

            // Hitung Moving Average untuk bulan ini
            $prediksi_stok = ($periods > 0) ? round(array_sum($monthlySales) / $periods) : 0;

            // Penjualan aktual adalah penjualan dari bulan sebelumnya
            $lastMonth = $currentPredictionMonth->copy()->subMonth();
            $penjualan_aktual_terakhir = DB::table('barang_transaksi')
                ->where('barang_id', $barang->id)
                ->whereBetween('created_at', [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()])
                ->sum('jumlah');

            // Buat satu baris hasil prediksi untuk bulan ini
            $prediksiJob->hasil()->create([
                'tanggal' => $currentPredictionMonth->toDateString(), // <-- TANGGAL DISIMPAN DI SINI
                'penjualan_aktual' => $penjualan_aktual_terakhir,
                'stok_aktual' => $barang->jumlah_stok, // Stok diambil saat ini
                'prediksi_stok' => $prediksi_stok,
            ]);
        }
    }
}
