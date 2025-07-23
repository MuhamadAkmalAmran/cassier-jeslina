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
                        DB::raw('SUM(barang_transaksi.jumlah) as total_kuantitas'),
                        DB::raw('SUM(barang_transaksi.jumlah * barang_transaksi.harga_satuan) as total_nilai')
                    )
                    ->join('barang_transaksi', 'barangs.id', '=', 'barang_transaksi.barang_id')
                    ->whereYear('barang_transaksi.created_at', $this->year)
                    ->whereMonth('barang_transaksi.created_at', $this->month)
                    ->groupBy('barangs.id', 'barangs.nama_barang')
            )
            ->columns([
                TextColumn::make('nama_barang'),
                TextColumn::make('total_kuantitas')
                    ->label('Total Kuantitas Terjual')
                    ->numeric(),
                TextColumn::make('total_nilai')
                    ->label('Total Nilai Penjualan')
                    ->money('IDR'),
            ])
            ->paginated(false);
    }

    // TAMBAHKAN METHOD BARU INI
    public function getTableRecordKey(object $record): string
    {
        // Gunakan nama_barang sebagai kunci unik untuk setiap baris
        return $record->nama_barang;
    }
}
