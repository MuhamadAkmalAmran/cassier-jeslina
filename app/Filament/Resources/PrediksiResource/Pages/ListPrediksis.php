<?php

namespace App\Filament\Resources\PrediksiResource\Pages;

use App\Filament\Resources\PrediksiResource;
use App\Models\Barang;
use App\Models\Prediksi;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Filament\Forms;

class ListPrediksis extends ListRecords
{
    protected static string $resource = PrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('buat_prediksi')
                ->label('Buat Prediksi Baru')
                ->icon('heroicon-o-chart-bar')
                ->form([
                    Forms\Components\Select::make('barang_id')
                        ->label('Pilih Barang')
                        ->options(Barang::query()->pluck('nama_barang', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('periode_data')
                        ->label('Gunakan Data Penjualan')
                        ->options([
                            '3-Bulan'  => '3 Bulan Terakhir',
                            '6-Bulan'  => '6 Bulan Terakhir',
                            '12-Bulan' => '1 Tahun Terakhir (12 Bulan)',
                        ])
                        ->default('3-Bulan')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // --- LOGIKA YANG SUDAH DISESUAIKAN ---

                    $barangId = $data['barang_id'];
                    $barang = Barang::find($barangId);
                    $periods = (int) $data['periode_data'];

                    // Kita akan memprediksi untuk bulan depan sebagai target utama
                    $targetPredictionDate = Carbon::now()->addMonth()->startOfMonth();

                    // LANGKAH 1: Buat satu "Job" Prediksi utama
                    $prediksiJob = Prediksi::create([
                        'barang_id' => $barangId,
                        'periode_data' => $data['periode_data'],
                        'tanggal_prediksi' => $targetPredictionDate,
                        'status' => 'Selesai',
                    ]);

                    // LANGKAH 2: Lakukan perulangan untuk membuat BEBERAPA hasil prediksi
                    // Loop ini akan berjalan untuk bulan target dan bulan-bulan sebelumnya
                    for ($k = 0; $k < $periods; $k++) {
                        $currentPredictionMonth = $targetPredictionDate->copy()->subMonths($k);

                        // Kumpulkan data historis untuk bulan yang sedang dihitung
                        $monthlySales = [];
                        for ($i = 1; $i <= $periods; $i++) {
                            $historyMonth = $currentPredictionMonth->copy()->subMonths($i);
                            $sales = DB::table('barang_transaksi')
                                ->where('barang_id', $barangId)
                                ->whereBetween('created_at', [$historyMonth->copy()->startOfMonth(), $historyMonth->copy()->endOfMonth()])
                                ->sum('jumlah');
                            $monthlySales[] = $sales;
                        }

                        $prediksi_stok = ($periods > 0) ? round(array_sum($monthlySales) / $periods) : 0;

                        $lastMonth = $currentPredictionMonth->copy()->subMonth();
                        $penjualan_aktual_terakhir = DB::table('barang_transaksi')
                            ->where('barang_id', $barangId)
                            ->whereBetween('created_at', [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()])
                            ->sum('jumlah');

                        // LANGKAH 3: Buat satu baris hasil prediksi untuk bulan ini
                        $prediksiJob->hasil()->create([
                            'tanggal' => $currentPredictionMonth->toDateString(),
                            'penjualan_aktual' => $penjualan_aktual_terakhir,
                            'stok_aktual' => $barang->jumlah_stok,
                            'prediksi_stok' => $prediksi_stok,
                        ]);
                    }

                    Notification::make()
                        ->title("Prediksi untuk '{$barang->nama_barang}' berhasil dibuat")
                        ->body("Silakan lihat hasil prediksi yang telah dibuat.")
                        ->success()
                        ->send();
                })
        ];
    }
}
