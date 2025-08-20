<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrediksiResource\Pages;
use App\Filament\Resources\PrediksiResource\RelationManagers;
use App\Models\Barang;
use App\Models\Prediksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PrediksiResource extends Resource
{
    protected static ?string $model = Prediksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Prediksi';

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('barang_id')
                    ->label('Barang')
                    ->options(Barang::query()->pluck('nama_barang', 'id')) // <-- Gunakan ini
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('periode_data')
                    ->label('Gunakan Data Penjualan')
                    ->options([
                        '3-Bulan' => '3 Bulan Terakhir',
                        '6-Bulan' => '6 Bulan Terakhir',
                        '12-Bulan' => '12 Bulan Terakhir',
                    ])
                    ->default('3-Bulan')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_prediksi')
                    ->label('Prediksi untuk Bulan')
                    ->default(now()->addMonth()->startOfMonth())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('periode_data')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_prediksi')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_hasil')
                    ->label('Lihat Hasil')
                    ->icon('heroicon-o-chart-bar')
                    // --- UBAH 'edit' MENJADI 'view' DI SINI ---
                    ->url(fn(Prediksi $record): string => self::getUrl('view', ['record' => $record])),

                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            RelationManagers\HasilRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrediksis::route('/'),
            'create' => Pages\CreatePrediksi::route('/create'),
            'edit' => Pages\EditPrediksi::route('/{record}/edit'),
            'view' => Pages\ViewPrediksi::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Prediksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('barang.nama_barang'),
                        Infolists\Components\TextEntry::make('periode_data'),
                        Infolists\Components\TextEntry::make('tanggal_prediksi')->date('F Y'),
                    ])->columns(3),
            ]);
    }
}
