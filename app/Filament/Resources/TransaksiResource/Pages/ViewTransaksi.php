<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol "Kembali" baru
            Actions\Action::make('back')
                ->label('Kembali')
                ->iconPosition('start')
                ->color('gray') // Beri warna agar tidak terlalu menonjol
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // HANYA ADA SATU SECTION UTAMA
                Section::make('Detail Transaksi Lengkap')
                    ->schema([
                        // Grid untuk informasi utama di bagian atas
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')->label('ID Transaksi'),
                                TextEntry::make('kasir.name')->label('Nama Kasir'),
                                TextEntry::make('created_at')->label('Tanggal Transaksi')->dateTime(),
                                TextEntry::make('total_harga_barang')->label('Total Belanja')->money('IDR'),
                                TextEntry::make('pembayaran.dibayar')->label('Uang Dibayar')->money('IDR'),
                                TextEntry::make('pembayaran.kembalian')->label('Uang Kembalian')->money('IDR'),
                            ]),

                        // RepeatableEntry untuk daftar barang, diletakkan di bawah Grid
                        RepeatableEntry::make('barangs')
                            ->label('Barang yang Dibeli')
                            ->contained(false)
                            ->schema([
                                TextEntry::make('nama_barang')->hiddenLabel(),
                                TextEntry::make('pivot.jumlah')->label('Jumlah'),
                                TextEntry::make('pivot.harga_satuan')->label('Harga Satuan')->money('IDR'),
                            ])
                            ->columns(3), // Samakan jumlah kolom agar rapi
                    ]),
            ]);
    }
}
