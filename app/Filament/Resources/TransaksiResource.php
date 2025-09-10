<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Models\Barang;
use App\Models\Transaksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                                        ->numeric(),
                                        // ->live(debounce: 500), // <-- PENTING
                                    Forms\Components\TextInput::make('harga_satuan')
                                        ->required()
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->readOnly(),

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
                Tables\Columns\TextColumn::make('kasir.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('barangs.nama_barang')
                    ->label('Daftar Barang')
                    ->formatStateUsing(function ($state, $record) {
                        // Ambil semua nama barang dari relasi dan gabungkan dengan koma
                        return $record->barangs->pluck('nama_barang')->implode(', ');
                    })
                    ->searchable(query: function ($query, string $search) {
                        // PERBAIKAN: Search berdasarkan nama barang dalam relasi many-to-many
                        return $query->whereHas('barangs', function ($q) use ($search) {
                            $q->where('nama_barang', 'like', "%{$search}%");
                        });
                    })
                    ->limit(40) // Batasi panjang teks agar UI rapi
                    ->tooltip(function ($record) { // Tampilkan daftar lengkap saat di-hover
                        return $record->barangs->pluck('nama_barang')->implode("\n");
                    }),
                Tables\Columns\TextColumn::make('pembayaran.total_pembayaran')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pembayaran.dibayar')
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filter berdasarkan kasir
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('kasir', 'name')
                    ->searchable(),

                // Filter berdasarkan barang
                Tables\Filters\SelectFilter::make('barang')
                    ->label('Barang')
                    ->options(Barang::pluck('nama_barang', 'id'))
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            return $query->whereHas('barangs', function ($q) use ($data) {
                                $q->where('barangs.id', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->searchable(),

                // Filter berdasarkan tanggal
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari_tanggal'], fn($q) => $q->whereDate('created_at', '>=', $data['dari_tanggal']))
                            ->when($data['sampai_tanggal'], fn($q) => $q->whereDate('created_at', '<=', $data['sampai_tanggal']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators['dari_tanggal'] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Admin bisa edit, kasir tidak
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()->role === 'admin'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->role === 'admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->role === 'admin'),
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
