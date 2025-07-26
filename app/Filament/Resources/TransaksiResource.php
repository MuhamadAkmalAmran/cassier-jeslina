<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Models\Barang;
use App\Models\Transaksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')->default(auth()->id()),
                Forms\Components\Hidden::make('total_harga_barang'),
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Pilih Barang')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->schema([
                                    Forms\Components\Select::make('barang_id')
                                        ->label('Barang')
                                        ->options(Barang::query()->where('jumlah_stok', '>', 0)->pluck('nama_barang', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->reactive() // <-- PENTING
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $barang = Barang::find($state);
                                            if ($barang) {
                                                $set('harga_satuan', $barang->harga_barang);
                                            }
                                        }),
                                    Forms\Components\TextInput::make('jumlah')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)
                                        ->reactive(), // <-- PENTING
                                    Forms\Components\TextInput::make('harga_satuan')
                                        ->required()
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->readOnly()
                                ])
                                ->columns(3)
                                ->defaultItems(1)
                                ->required()
                                ->live() // <-- PENTING: Menggantikan reactive() pada repeater
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    // Panggil fungsi untuk update total
                                    self::updateTotals($get, $set);
                                })
                                ->deleteAction(
                                    fn(Forms\Components\Actions\Action $action) => $action->after(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                ),
                        ]),
                    Forms\Components\Wizard\Step::make('Pembayaran')
                        ->schema([
                            Forms\Components\TextInput::make('total_pembayaran')
                                ->label('Total Belanja')
                                ->prefix('Rp')
                                ->readOnly() // <-- Dibuat read-only karena dihitung otomatis
                                ->numeric(),
                            Forms\Components\TextInput::make('dibayar')
                                ->label('Uang Dibayar')
                                ->prefix('Rp')
                                ->numeric()
                                ->required()
                                ->live(onBlur: true) // <-- PENTING: Update saat fokus hilang
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                }),
                            Forms\Components\TextInput::make('kembalian')
                                ->label('Uang Kembalian')
                                ->prefix('Rp')
                                ->readOnly() // <-- Dibuat read-only
                                ->numeric(),
                        ])
                ])->columnSpanFull()
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        // Ambil semua item barang dari repeater
        $items = $get('items');
        $total = 0;

        // Hitung total dari semua item
        if (is_array($items)) {
            foreach ($items as $item) {
                // Pastikan jumlah dan harga_satuan ada dan numerik
                $jumlah = is_numeric($item['jumlah']) ? $item['jumlah'] : 0;
                $harga = is_numeric($item['harga_satuan']) ? $item['harga_satuan'] : 0;
                $total += $jumlah * $harga;
            }
        }

        // Set nilai 'total_pembayaran'
        $set('total_pembayaran', $total);

        // Set nilai 'total_harga_barang'
        $set('total_harga_barang', $total);

        // Hitung dan set kembalian
        $dibayar = is_numeric($get('dibayar')) ? $get('dibayar') : 0;
        $kembalian = $dibayar - $total;
        $set('kembalian', $kembalian);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID Transaksi')->sortable(),
                Tables\Columns\TextColumn::make('kasir.name')->searchable()->sortable(),
                                Tables\Columns\TextColumn::make('barangs.nama_barang')
                    ->label('Daftar Barang')
                    ->formatStateUsing(function ($state, $record) {
                        // Ambil semua nama barang dari relasi dan gabungkan dengan koma
                        return $record->barangs->pluck('nama_barang')->implode(', ');
                    })
                    ->limit(40) // Batasi panjang teks agar UI rapi
                    ->tooltip(function ($record) { // Tampilkan daftar lengkap saat di-hover
                        return $record->barangs->pluck('nama_barang')->implode("\n");
                    }),
                Tables\Columns\TextColumn::make('total_harga_barang')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('pembayaran.dibayar')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Admin bisa edit, kasir tidak
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn() => auth()->user()->role === 'admin'),
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
            // RelationManagers\BarangsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'),
            'create' => Pages\CreateTransaksi::route('/create'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
            'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}
