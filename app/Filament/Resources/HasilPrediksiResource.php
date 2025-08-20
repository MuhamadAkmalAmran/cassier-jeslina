<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HasilPrediksiResource\Pages;
use App\Models\HasilPrediksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HasilPrediksiResource extends Resource
{
    protected static ?string $model = HasilPrediksi::class;
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('prediksi_id')->disabled(),
                Forms\Components\TextInput::make('penjualan_aktual')->disabled()->nullable(),
                Forms\Components\TextInput::make('stok_aktual')->disabled(),
                Forms\Components\TextInput::make('prediksi_stok')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prediksi.barang.nama_barang')->label('Nama Barang')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('prediksi.tanggal_prediksi')->label('Bulan Prediksi')->date('F Y')->sortable(),
                Tables\Columns\TextColumn::make('prediksi.periode_data')->label('Periode Data')->badge(),
                Tables\Columns\TextColumn::make('penjualan_aktual')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('stok_aktual')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('prediksi_stok')->numeric()->sortable()->weight('bold'),
            ])
            ->defaultSort('prediksi.tanggal_prediksi', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Hasil Prediksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('prediksi.barang.nama_barang')->label('Nama Barang'),
                        Infolists\Components\TextEntry::make('prediksi.tanggal_prediksi')->label('Prediksi untuk Bulan')->date('F Y'),
                        Infolists\Components\TextEntry::make('prediksi.periode_data')->label('Berdasarkan Data'),
                    ])->columns(3),

                Infolists\Components\Section::make('Hasil Perhitungan')
                    ->schema([
                        Infolists\Components\TextEntry::make('penjualan_aktual')->label('Penjualan Aktual'),
                        Infolists\Components\TextEntry::make('stok_aktual')->label('Stok Saat Ini'),
                        Infolists\Components\TextEntry::make('prediksi_stok')->label('Prediksi Penjualan Bulan Depan')->weight('bold'),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHasilPrediksis::route('/'),
            'edit' => Pages\EditHasilPrediksi::route('/{record}/edit'),
            'view' => Pages\ViewHasilPrediksi::route('/{record}'),
        ];
    }
}
