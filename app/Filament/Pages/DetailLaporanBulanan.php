<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Actions\Action;

class DetailLaporanBulanan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.detail-laporan-bulanan';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $year = null;
    public ?int $month = null;

    public function mount(): void
    {
        $this->year = request()->query('year');
        $this->month = request()->query('month');

        if (!$this->year || !$this->month) {
            redirect(LaporanPenjualanBulanan::getUrl());
        }
    }

    public function getTitle(): string
    {
        return 'Detail Penjualan - ' . Carbon::createFromDate($this->year, $this->month)->translatedFormat('F Y');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Barang::query()
                    ->select(
                        'barangs.nama_barang',
                        DB::raw('SUM(barang_transaksi.jumlah) AS total_kuantitas'),
                        DB::raw('SUM(barang_transaksi.jumlah * barang_transaksi.harga_satuan) AS total_nilai'),
                        'hp.prediksi_stok',
                        'hp.tanggal'
                    )
                    ->join('barang_transaksi', 'barangs.id', '=', 'barang_transaksi.barang_id')
                    ->leftJoin('prediksis AS p', 'barangs.id', '=', 'p.barang_id')
                    ->leftJoin(DB::raw("
                                        (
                                            SELECT h1.*
                                            FROM hasil_prediksis h1
                                            INNER JOIN (
                                                SELECT prediksi_id, MAX(tanggal) AS max_tanggal
                                                FROM hasil_prediksis
                                                WHERE YEAR(tanggal) = {$this->year}
                                                AND MONTH(tanggal) = {$this->month}
                                                GROUP BY prediksi_id
                                            ) h2 ON h1.prediksi_id = h2.prediksi_id AND h1.tanggal = h2.max_tanggal
                                        ) AS hp
                            "), 'p.id', '=', 'hp.prediksi_id')
                    ->whereYear('barang_transaksi.created_at', $this->year)
                    ->whereMonth('barang_transaksi.created_at', $this->month)
                    ->groupBy('barangs.id', 'barangs.nama_barang', 'hp.prediksi_stok', 'hp.tanggal')
                    ->orderByDesc('hp.tanggal')
            )

            ->columns([
                TextColumn::make('nama_barang'),
                TextColumn::make('total_kuantitas')
                    ->label('Total Terjual')
                    ->numeric(),
                TextColumn::make('total_nilai')
                    ->label('Total Penjualan')
                    ->money('IDR'),
                TextColumn::make('prediksi_stok')
                    ->label('Prediksi Stok')
                    ->numeric()
                    ->default(0), // Tampilkan 0 jika tidak ada data prediksi
            ])
            ->paginated(false);
    }

    // TAMBAHKAN METHOD BARU INI
    public function getTableRecordKey(object $record): string
    {
        // Gunakan nama_barang sebagai kunci unik untuk setiap baris
        return $record->nama_barang;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(LaporanPenjualanBulanan::getUrl())
                ->outlined(),
        ];
    }
}
