<?php

namespace App\Filament\Resources\PrediksiResource\Pages;

use App\Filament\Resources\PrediksiResource;
use App\Models\Barang;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePrediksi extends CreateRecord
{
    protected static string $resource = PrediksiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        $prediksiJob = $this->record;
        $barang = Barang::find($prediksiJob->barang_id);
        $periods = (int) $prediksiJob->periode_data; // Misal: 3
        $targetPredictionDate = Carbon::parse($prediksiJob->tanggal_prediksi); // Misal: September 2025

        // --- FUNGSI BANTU UNTUK MENGHITUNG PREDIKSI ---
        $calculatePrediction = function (Carbon $currentMonth) use ($barang, $periods) {
            $historicalSales = [];
            for ($i = 1; $i <= $periods; $i++) {
                $historyMonth = $currentMonth->copy()->subMonths($i);
                $sales = DB::table('barang_transaksi')
                    ->where('barang_id', $barang->id)
                    ->whereBetween('created_at', [$historyMonth->copy()->startOfMonth(), $historyMonth->copy()->endOfMonth()])
                    ->sum('jumlah');
                $historicalSales[] = $sales;
            }
            return ($periods > 0) ? round(array_sum($historicalSales) / $periods) : 0;
        };

        // --- LOGIKA UTAMA: BUAT HASIL UNTUK (PERIODE + 1) BULAN ---
        // Jika periode = 3 & target = September, loop akan berjalan 4 kali (k=0, 1, 2, 3)
        // untuk menghitung bulan September, Agustus, Juli, dan Juni.
        for ($k = 0; $k <= $periods; $k++) {
            $currentMonth = $targetPredictionDate->copy()->subMonths($k);

            // --- PERBAIKAN 1: HITUNG PENJUALAN AKTUAL UNTUK BULAN INI ---
            $penjualan_aktual = DB::table('barang_transaksi')
                ->where('barang_id', $barang->id)
                ->whereBetween('created_at', [
                    $currentMonth->copy()->startOfMonth(),
                    $currentMonth->copy()->endOfMonth(),
                ])
                ->sum('jumlah');

            // --- PERBAIKAN 2: HITUNG STOK AKTUAL HISTORIS ---
            // Stok di akhir bulan = stok sekarang + semua penjualan sejak akhir bulan itu
            $salesSinceThen = DB::table('barang_transaksi')
                ->where('barang_id', $barang->id)
                ->where('created_at', '>', $currentMonth->copy()->endOfMonth())
                ->sum('jumlah');
            $stok_aktual = $barang->jumlah_stok + $salesSinceThen;

            // --- PERBAIKAN 3: LOGIKA KHUSUS UNTUK BULAN TARGET PREDIKSI ---
            // Jika bulan yang dihitung adalah bulan target (di masa depan),
            // penjualan aktualnya belum ada, dan stok aktualnya adalah stok saat ini.
            if ($k == 0) {
                $penjualan_aktual = 0; // Penjualan belum terjadi
                $stok_aktual = $barang->jumlah_stok; // Gunakan stok saat ini
            }

            // Hitung prediksi Moving Average untuk bulan ini
            $prediksi_dihitung = $calculatePrediction($currentMonth);

            // Simpan hasil
            $prediksiJob->hasil()->create([
                'tanggal'    => $currentMonth->toDateString(),
                'penjualan_aktual' => $penjualan_aktual,
                'stok_aktual'      => $stok_aktual,
                'prediksi_stok'    => $prediksi_dihitung,
            ]);
        }

        // Update status job utama
        $prediksiJob->update(['status' => 'Selesai']);
    }
}
