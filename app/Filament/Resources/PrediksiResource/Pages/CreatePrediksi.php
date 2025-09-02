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
        $periods = $prediksiJob->periode_data; // contoh: 3
        $targetPredictionDate = Carbon::parse($prediksiJob->tanggal_prediksi); // contoh: September 2025

        // DEBUG: Log parameter
        Log::info("=== PREDIKSI DEBUG ===");
        Log::info("Periods: {$periods}");
        Log::info("Target Date: {$targetPredictionDate->format('Y-m-d')}");

        // Fungsi bantu: hitung moving average berdasarkan data historis
        $calculateMovingAverage = function (Carbon $predictionMonth, int $periodsToUse) use ($barang) {
            $historicalSales = [];

            // Ambil data penjualan dari bulan-bulan sebelum bulan prediksi
            for ($i = 1; $i <= $periodsToUse; $i++) {
                $historyMonth = $predictionMonth->copy()->subMonths($i);
                $sales = DB::table('barang_transaksi')
                    ->where('barang_id', $barang->id)
                    ->whereBetween('created_at', [
                        $historyMonth->copy()->startOfMonth(),
                        $historyMonth->copy()->endOfMonth(),
                    ])
                    ->sum('jumlah');

                $historicalSales[] = $sales;
            }

            return ($periodsToUse > 0 && count($historicalSales) > 0)
                ? round(array_sum($historicalSales) / count($historicalSales))
                : 0;
        };

        // PERBAIKAN: Loop yang benar untuk menghasilkan ($periods + 1) bulan
        // Jika periode_data = 3 dan tanggal_prediksi = September 2025
        // Loop: k=3,2,1,0 menghasilkan: Juni, Juli, Agustus, September (4 bulan total)

        for ($k = $periods; $k >= 0; $k--) {
            // PERBAIKAN: Gunakan subMonths (bukan addMonths)
            $currentMonth = $targetPredictionDate->copy()->subMonths($k);

            Log::info("k={$k}, Current Month: {$currentMonth->format('Y-m-d')}");

            // Untuk bulan historis (k > 0): ambil penjualan aktual
            // Untuk bulan prediksi (k = 0): penjualan_aktual = 0 (belum terjadi)
            if ($k > 0) {
                // Ini adalah bulan historis - ambil penjualan aktual bulan tersebut
                $penjualan_aktual = DB::table('barang_transaksi')
                    ->where('barang_id', $barang->id)
                    ->whereBetween('created_at', [
                        $currentMonth->copy()->startOfMonth(),
                        $currentMonth->copy()->endOfMonth(),
                    ])
                    ->sum('jumlah');

                Log::info("Bulan historis - Penjualan: {$penjualan_aktual}");
            } else {
                // Ini adalah bulan prediksi - penjualan belum terjadi
                $penjualan_aktual = 0;
                Log::info("Bulan prediksi - Penjualan: 0");
            }

            // Hitung prediksi menggunakan moving average
            $prediksi_stok = $calculateMovingAverage($currentMonth, $periods);

            // Stok aktual saat ini (bisa disesuaikan dengan kebutuhan bisnis)
            $stok_aktual = $barang->jumlah_stok;

            // Simpan hasil
            $hasil = $prediksiJob->hasil()->create([
                'tanggal'          => $currentMonth->toDateString(),
                'penjualan_aktual' => $penjualan_aktual,
                'stok_aktual'      => $stok_aktual,
                'prediksi_stok'    => $prediksi_stok,
            ]);

            Log::info("Created record ID: {$hasil->id} for date: {$currentMonth->toDateString()}");
        }

        // DEBUG: Hitung total record yang dibuat
        $totalRecords = $prediksiJob->hasil()->count();
        Log::info("Total records created: {$totalRecords}");

        // Update status prediksi job menjadi completed
        $prediksiJob->update(['status' => 'Selesai']);
        Log::info("=== END DEBUG ===");
    }
}
