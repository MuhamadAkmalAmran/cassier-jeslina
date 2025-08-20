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
use Throwable;

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
                    try {
                        DB::transaction(function () use ($data) {
                            $barangId = $data['barang_id'];
                            $barang = Barang::findOrFail($barangId);

                            // convert pilihan periode menjadi angka (3, 6, 12)
                            $periods = (int) filter_var($data['periode_data'], FILTER_SANITIZE_NUMBER_INT);

                            // target prediksi = bulan depan
                            $targetPredictionDate = Carbon::now()->addMonth()->startOfMonth();

                            // Buat job prediksi utama
                            $prediksiJob = Prediksi::create([
                                'barang_id' => $barangId,
                                'periode_data' => $data['periode_data'],
                                'tanggal_prediksi' => $targetPredictionDate,
                                'status' => 'Selesai',
                            ]);

                            // Simpan stok terkini sebagai basis
                            $stokSekarang = $barang->jumlah_stok;

                            // Loop sebanyak periode
                            for ($k = 0; $k < $periods; $k++) {
                                $currentPredictionMonth = $targetPredictionDate->copy()->subMonths($k);

                                // === Hitung prediksi stok (rata-rata penjualan n bulan ke belakang) ===
                                $monthlySales = [];
                                for ($i = 1; $i <= $periods; $i++) {
                                    $historyMonth = $currentPredictionMonth->copy()->subMonths($i);
                                    $sales = DB::table('barang_transaksi')
                                        ->where('barang_id', $barangId)
                                        ->whereBetween('created_at', [
                                            $historyMonth->copy()->startOfMonth(),
                                            $historyMonth->copy()->endOfMonth()
                                        ])
                                        ->sum('jumlah');
                                    $monthlySales[] = $sales;
                                }
                                $prediksi_stok = ($periods > 0) ? round(array_sum($monthlySales) / $periods) : 0;

                                // === Ambil penjualan aktual hanya jika bulan <= sekarang ===
                                if ($currentPredictionMonth->greaterThan(Carbon::now()->startOfMonth())) {
                                    $penjualan_aktual_terakhir = null;
                                } else {
                                    $lastMonth = $currentPredictionMonth->copy()->subMonth();
                                    $penjualan_aktual_terakhir = DB::table('barang_transaksi')
                                        ->where('barang_id', $barangId)
                                        ->whereBetween('created_at', [
                                            $lastMonth->copy()->startOfMonth(),
                                            $lastMonth->copy()->endOfMonth()
                                        ])
                                        ->sum('jumlah');
                                }

                                // === Hitung stok aktual bulan prediksi ===
                                $totalPenjualanSetelah = DB::table('barang_transaksi')
                                    ->where('barang_id', $barangId)
                                    ->whereBetween('created_at', [
                                        $currentPredictionMonth->copy()->startOfMonth(),
                                        now()->endOfMonth()
                                    ])
                                    ->sum('jumlah');

                                $stokAktual = $stokSekarang + $totalPenjualanSetelah;

                                // Simpan hasil prediksi
                                $prediksiJob->hasil()->create([
                                    'tanggal' => $currentPredictionMonth->toDateString(),
                                    'penjualan_aktual' => $penjualan_aktual_terakhir ?? 0,
                                    'stok_aktual' => $stokAktual,
                                    'prediksi_stok' => $prediksi_stok,
                                ]);
                            }
                        });

                        Notification::make()
                            ->title("Prediksi berhasil dibuat")
                            ->body("Silakan lihat hasil prediksi yang telah dibuat.")
                            ->success()
                            ->send();

                    } catch (Throwable $e) {
                        // Kalau ada error, rollback otomatis dan tampilkan notifikasi gagal
                        Notification::make()
                            ->title("Prediksi gagal")
                            ->body("Terjadi kesalahan: " . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];
    }

    public function getTitle(): string
    {
        return 'Daftar Prediksi';
    }
}
