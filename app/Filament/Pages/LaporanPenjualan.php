<?php

namespace App\Filament\Pages;

use App\Models\Transaksi;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Carbon\Carbon;

class LaporanPenjualanBulanan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.laporan-penjualan-bulanan';
    protected static ?string $title = 'Laporan Penjualan Bulanan';
    protected static ?string $navigationGroup = 'Laporan';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaksi::query()
                    ->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('SUM(total_harga_barang) as total_penjualan')
                    )
                    ->groupBy('year', 'month')
            )
            // HAPUS ->recordKey(...) DARI SINI
            ->columns([
                TextColumn::make('periode')
                    ->label('Periode Bulan')
                    ->state(function (object $record): string {
                        return Carbon::createFromDate($record->year, $record->month)->translatedFormat('F Y');
                    })
                    ->sortable(['year', 'month']),
                TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->actions([
                Action::make('lihat_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(function ($record): string {
                        return DetailLaporanBulanan::getUrl([
                            'year' => $record->year,
                            'month' => $record->month
                        ]);
                    }),
            ])
            ->defaultSort('year', 'desc');
    }

    // TAMBAHKAN METHOD BARU INI
    public function getTableRecordKey(object $record): string
    {
        return $record->year . '-' . $record->month;
    }
}
