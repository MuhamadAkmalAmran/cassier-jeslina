<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\HasilPrediksi;
use App\Models\Prediksi;
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
    public ?int $nextMonth = null;

    public function mount(): void
    {
        $this->year = request()->query('year');
        $this->month = request()->query('month');
        $this->nextMonth = request()->query('month') + 1;


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
                    ->select('barangs.nama_barang')
                    ->selectSub(function ($query) {
                        $query->from('barang_transaksi')
                            ->selectRaw('SUM(jumlah)')
                            ->whereColumn('barang_transaksi.barang_id', 'barangs.id');
                    }, 'total_kuantitas')
                    ->selectSub(function ($query) {
                        $query->from('barang_transaksi')
                            ->selectRaw('SUM(jumlah * harga_satuan)')
                            ->whereColumn('barang_transaksi.barang_id', 'barangs.id');
                    }, 'total_nilai')
                    ->addSelect(['hp.prediksi_stok', 'hp.tanggal'])
                    ->join('barang_transaksi', 'barangs.id', '=', 'barang_transaksi.barang_id')
                    ->leftJoinSub(
                        Prediksi::select('barang_id', DB::raw('MAX(id) AS id'))
                            ->groupBy('barang_id'),
                        'p',
                        'barangs.id',
                        '=',
                        'p.barang_id'
                    )
                    ->leftJoinSub(
                        HasilPrediksi::from('hasil_prediksis as h1')
                            ->joinSub(
                                HasilPrediksi::select('prediksi_id')
                                    ->selectRaw('MAX(tanggal) AS max_tanggal')
                                    ->whereYear('tanggal', $this->year)
                                    ->whereMonth('tanggal', $this->nextMonth)
                                    ->groupBy('prediksi_id'),
                                'h2',
                                function ($join) {
                                    $join->on('h1.prediksi_id', '=', 'h2.prediksi_id')
                                        ->on('h1.tanggal', '=', 'h2.max_tanggal');
                                }
                            )
                            ->select('h1.*'),
                        'hp',
                        'p.id',
                        '=',
                        'hp.prediksi_id'
                    )
                    ->whereYear('barang_transaksi.created_at', $this->year)
                    ->whereMonth('barang_transaksi.created_at', $this->month)
                    ->groupBy('barangs.id', 'barangs.nama_barang', 'hp.prediksi_stok', 'hp.tanggal')
                    ->orderBy('barangs.id', 'asc')
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
                    ->label(function () {
                        $bulan = \Carbon\Carbon::createFromDate($this->year, $this->nextMonth)->translatedFormat('F Y');
                        return 'Prediksi Stok (' . $bulan . ')';
                    })
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
