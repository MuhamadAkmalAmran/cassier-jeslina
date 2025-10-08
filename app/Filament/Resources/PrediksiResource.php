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
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

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
                    ->options(Barang::query()->pluck('nama_barang', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('periode_data')
                    ->label('Gunakan Data Penjualan')
                    ->options([
                        3 => '3 Bulan Terakhir',
                        6 => '6 Bulan Terakhir',
                        12 => '12 Bulan Terakhir',
                    ])
                    ->default(3)
                    ->required(),
                Forms\Components\Hidden::make('tanggal_prediksi')
                    ->label('Prediksi untuk Bulan')
                    ->default(now()->addMonth()->startOfMonth())
                    ->required(),
                // Tambahkan field status dengan default value
                Forms\Components\Hidden::make('status')
                    ->default('processing'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('periode_data')
                    ->label('Periode Data')
                    ->suffix(' bulan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_prediksi')
                    ->label('Bulan Prediksi')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('barang')
                    ->relationship('barang', 'nama_barang')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_hasil')
                    ->label('Lihat Hasil')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn(Prediksi $record): string => self::getUrl('view', ['record' => $record]))
                    ->visible(fn(Prediksi $record): bool => $record->status === 'Selesai'),

                Tables\Actions\DeleteAction::make(),
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
            // RelationManagers\HasilRelationManager::class,
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
                // Section untuk informasi umum prediksi
                Infolists\Components\Section::make('Informasi Prediksi')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('barang.nama_barang')
                                    ->label('Nama Barang'),
                                Infolists\Components\TextEntry::make('periode_data')
                                    ->label('Periode Data')
                                    ->suffix(' bulan'),
                                Infolists\Components\TextEntry::make('tanggal_prediksi')
                                    ->label('Bulan Prediksi')
                                    ->date('F Y'),
                            ]),
                    ]),

                // Section untuk hasil prediksi utama (bulan yang diprediksi)
                Infolists\Components\Section::make('Hasil Prediksi Utama')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('penjualan_bulan_lalu')
                                    ->label('Penjualan Bulan Sebelumnya')
                                    ->state(function (Prediksi $record) {
                                        // Ambil data penjualan bulan sebelum bulan prediksi
                                        $bulanSebelumnya = \Carbon\Carbon::parse($record->tanggal_prediksi)->subMonth();
                                        $hasil = $record->hasil()->whereDate('tanggal', $bulanSebelumnya->toDateString())->first();
                                        return $hasil?->penjualan_aktual ?? 0;
                                    }),

                                Infolists\Components\TextEntry::make('stok_saat_ini')
                                    ->label('Stok Aktual Saat Ini')
                                    ->state(function (Prediksi $record) {
                                        // Ambil stok dari bulan prediksi
                                        $hasil = $record->hasil()->whereDate('tanggal', $record->tanggal_prediksi)->first();
                                        return $hasil?->stok_aktual ?? $record->barang->jumlah_stok;
                                    }),

                                Infolists\Components\TextEntry::make('prediksi_penjualan')
                                    ->label('Prediksi Penjualan')
                                    ->weight(FontWeight::Bold)
                                    ->color('success')
                                    ->state(function (Prediksi $record) {
                                        // Ambil prediksi untuk bulan target
                                        $hasil = $record->hasil()->whereDate('tanggal', $record->tanggal_prediksi)->first();
                                        return $hasil?->prediksi_stok ?? 0;
                                    }),
                            ]),
                    ])
                    ->collapsible(),

                // Section untuk tabel riwayat data perhitungan lengkap
                Infolists\Components\Section::make('Hasil Prediksi')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('hasil')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('tanggal')
                                            ->label('Bulan')
                                            ->formatStateUsing(function (string $state): string {
                                                return \Carbon\Carbon::parse($state)->format('F Y');
                                            })
                                            ->weight('bold'),

                                        Infolists\Components\TextEntry::make('penjualan_aktual')
                                            ->label('Penjualan Aktual')
                                            ->formatStateUsing(function ($state): string {
                                                return $state === 0 ? 0 : number_format($state);
                                            }),

                                        Infolists\Components\TextEntry::make('stok_aktual')
                                            ->label('Stok Aktual')
                                            ->formatStateUsing(function ($state): string {
                                                return number_format($state);
                                            }),

                                        Infolists\Components\TextEntry::make('prediksi_stok')
                                            ->label('Prediksi Penjualan')
                                            ->formatStateUsing(function ($state): string {
                                                return number_format($state);
                                            })
                                            ->weight('bold')
                                            ->color('success'),
                                    ])
                            ])
                            ->state(function (Prediksi $record) {
                                // Ambil SEMUA hasil termasuk bulan prediksi (4 bulan total)
                                $results = $record->hasil()
                                    ->orderBy('tanggal', 'asc') // Urutkan dari yang terlama
                                    ->get();

                                // Debug: Log jumlah hasil yang ditemukan
                                Log::info("Found {$results->count()} results for prediksi ID {$record->id}");

                                return $results->toArray();
                            }),
                    ])
                    ->collapsible(),
            ]);
    }
}
