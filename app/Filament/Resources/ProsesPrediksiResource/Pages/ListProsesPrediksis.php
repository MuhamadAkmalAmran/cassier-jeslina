<?php

namespace App\Filament\Resources\ProsesPrediksiResource\Pages;

use App\Filament\Resources\ProsesPrediksiResource;
use App\Models\Barang;
use App\Models\ProsesPrediksi;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Filament\Forms;

class ListProsesPrediksis extends ListRecords
{
    protected static string $resource = ProsesPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Ini adalah aksi yang akan membuat tombol dan form
            Actions\Action::make('buat_prediksi')
                ->label('Buat Prediksi Bulanan')
                ->icon('heroicon-o-chart-bar')
                ->form([
                    // Form untuk memilih bulan dan tahun prediksi
                    Forms\Components\Select::make('bulan')
                        ->options([
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember',
                        ])
                        ->default(now()->month)
                        ->required(),
                    Forms\Components\Select::make('tahun')
                        ->options([
                            now()->year - 1 => now()->year - 1,
                            now()->year => now()->year,
                            now()->year + 1 => now()->year + 1,
                        ])
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $periods = 3;
                    $predictionYear = $data['tahun'];
                    $barangs = Barang::all();
                    $predictionsMade = 0;

                    // Loop untuk membuat prediksi untuk setiap bulan di tahun terpilih
                    for ($month = 1; $month <= 12; $month++) {
                        $currentPredictionDate = Carbon::createFromDate($predictionYear, $month, 1)->startOfMonth();

                        foreach ($barangs as $barang) {
                            $monthlySales = [];
                            for ($i = 1; $i <= $periods; $i++) {
                                $historyMonthDate = $currentPredictionDate->copy()->subMonths($i);

                                // --- PERUBAHAN QUERY DI SINI ---
                                // Kita gunakan rentang tanggal (awal dan akhir bulan) agar lebih akurat
                                $sales = DB::table('barang_transaksi')
                                    ->where('barang_id', $barang->id)
                                    ->whereBetween('created_at', [
                                        $historyMonthDate->copy()->startOfMonth(),
                                        $historyMonthDate->copy()->endOfMonth(),
                                    ])
                                    ->sum('jumlah');
                                // --- AKHIR PERUBAHAN ---

                                $monthlySales[] = $sales;
                            }

                            $totalSalesLastPeriods = array_sum($monthlySales);
                            $dataPoints = count($monthlySales);
                            $prediksi_stok = ($dataPoints > 0) ? round($totalSalesLastPeriods / $dataPoints) : 0;

                            $lastMonthDate = $currentPredictionDate->copy()->subMonth();
                            // --- PERUBAHAN QUERY DI SINI JUGA ---
                            $penjualan_aktual_terakhir = DB::table('barang_transaksi')
                                ->where('barang_id', $barang->id)
                                ->whereBetween('created_at', [
                                    $lastMonthDate->copy()->startOfMonth(),
                                    $lastMonthDate->copy()->endOfMonth(),
                                ])
                                ->sum('jumlah');
                            // --- AKHIR PERUBAHAN ---

                            $stok_aktual = $barang->jumlah_stok;

                            ProsesPrediksi::updateOrCreate(
                                ['barang_id' => $barang->id, 'tanggal' => $currentPredictionDate->toDateString()],
                                ['penjualan_aktual' => $penjualan_aktual_terakhir, 'stok_aktual' => $stok_aktual, 'prediksi_stok' => $prediksi_stok]
                            );

                            // Cek jika barang ini yang kita debug di SQL
                            if ($barang->id == 1 && $currentPredictionDate->month == 7) {
                                dump([
                                    'Barang' => $barang->nama_barang,
                                    'Prediksi Untuk' => $currentPredictionDate->format('F Y'),
                                    'Penjualan Aktual (Juni)' => $penjualan_aktual_terakhir,
                                    'Prediksi Stok' => $prediksi_stok,
                                    'Data Historis' => $monthlySales,
                                ]);
                            }
                        }
                    }

                    // Kirim notifikasi sukses
                    Notification::make()
                        ->title("Prediksi berhasil dibuat")
                        ->body("Berhasil membuat prediksi untuk tahun {$predictionYear}")
                        ->success()
                        ->send();
                })
        ];
    }
}
