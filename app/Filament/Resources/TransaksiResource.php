<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Models\Barang;
use App\Models\Transaksi;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
                        ->afterValidation(function (Get $get, Set $set) { // PERBAIKAN 2: Tambah Set parameter
                            $items = $get('items');

                            // PERBAIKAN 3: Validasi items tidak kosong
                            if (empty($items) || !is_array($items)) {
                                throw ValidationException::withMessages([
                                    'items' => 'Minimal pilih satu barang.',
                                ]);
                            }

                            foreach ($items as $key => $item) {
                                // Skip item kosong
                                if (empty($item['barang_id']) || empty($item['jumlah'])) {
                                    continue;
                                }

                                $barang = Barang::find($item['barang_id']);
                                if ($barang && $item['jumlah'] > $barang->jumlah_stok) {
                                    Notification::make()
                                        ->title('Stok Tidak Cukup!')
                                        ->body("Stok untuk {$barang->nama_barang} tidak mencukupi. Sisa: {$barang->jumlah_stok}")
                                        ->danger()
                                        ->send();

                                    throw ValidationException::withMessages([
                                        "data.items.{$key}.jumlah" => "Stok untuk {$barang->nama_barang} tidak mencukupi. Sisa: {$barang->jumlah_stok}",
                                    ]);
                                }
                            }

                            // PERBAIKAN 4: Update total setelah validasi berhasil
                            self::updateTotals($get, $set);
                        })
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->schema([
                                    Forms\Components\Select::make('barang_id')
                                        ->label('Barang')
                                        ->options(Barang::query()->where('jumlah_stok', '>', 0)->pluck('nama_barang', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->live() // PERBAIKAN 5: Ganti reactive dengan live
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $barang = Barang::find($state);
                                            if ($barang) {
                                                $set('harga_satuan', $barang->harga_barang);
                                                // Update total setelah barang dipilih
                                                self::updateTotals($get, $set);
                                            }
                                        }),
                                    Forms\Components\TextInput::make('jumlah')
                                        ->required()
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            // PERBAIKAN 6: Update total setiap kali jumlah berubah
                                            self::updateTotals($get, $set);
                                        })
                                        ->rules([
                                            function (Get $get) {
                                                return function (string $attribute, $value, Closure $fail) use ($get) {
                                                    $barangId = $get('../barang_id');
                                                    if (!$barangId) {
                                                        return;
                                                    }

                                                    $barang = Barang::find($barangId);
                                                    if (!$barang) {
                                                        return;
                                                    }

                                                    if ($value > $barang->jumlah_stok) {
                                                        $fail("Stok tidak mencukupi. Stok tersedia: {$barang->jumlah_stok}.");
                                                    }
                                                };
                                            },
                                        ]),
                                    Forms\Components\TextInput::make('harga_satuan')
                                        ->required()
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->readOnly()
                                        ->live() // PERBAIKAN 7: Buat live untuk update otomatis
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::updateTotals($get, $set);
                                        }),
                                ])
                                ->columns(3)
                                ->defaultItems(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                })
                                ->deleteAction(
                                    fn(Forms\Components\Actions\Action $action) => $action->after(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                )
                                ->addAction(
                                    fn(Forms\Components\Actions\Action $action) => $action->after(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                ),
                        ]),
                    Forms\Components\Wizard\Step::make('Pembayaran')
                        ->schema([
                            Forms\Components\TextInput::make('total_pembayaran')
                                ->label('Total Belanja')
                                ->prefix('Rp')
                                ->readOnly()
                                ->numeric()
                                ->live(), // PERBAIKAN 8: Buat live
                            Forms\Components\TextInput::make('dibayar')
                                ->label('Uang Dibayar')
                                ->prefix('Rp')
                                ->numeric()
                                ->required()
                                ->live(onBlur: true)
                                ->rules([ // Tambahkan rules() di sini
                                    function (Get $get) {
                                        return function (string $attribute, $value, Closure $fail) use ($get) {
                                            // Ambil nilai dari total_pembayaran
                                            $totalBelanja = (float) $get('total_pembayaran');
                                            $dibayar = (float) $value;

                                            // Bandingkan
                                            if ($dibayar < $totalBelanja) {
                                                // Jika kurang, kirim pesan error
                                                $fail("Jumlah yang dibayar tidak boleh kurang dari total belanja.");
                                            }
                                        };
                                    },
                                ])
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                }),
                            Forms\Components\TextInput::make('kembalian')
                                ->label('Uang Kembalian')
                                ->prefix('Rp')
                                ->readOnly()
                                ->numeric()
                                ->live(), // PERBAIKAN 9: Buat live
                        ])
                ])->columnSpanFull()
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items');
        $total = 0;

        if (is_array($items)) {
            foreach ($items as $item) {
                // PERBAIKAN 10: Validasi data lebih ketat
                if (empty($item['barang_id']) || empty($item['jumlah']) || empty($item['harga_satuan'])) {
                    continue;
                }

                $jumlah = is_numeric($item['jumlah']) ? floatval($item['jumlah']) : 0;
                $harga = is_numeric($item['harga_satuan']) ? floatval($item['harga_satuan']) : 0;
                $total += $jumlah * $harga;
            }
        }

        // PERBAIKAN 11: Set dengan logging untuk debugging
        $set('total_pembayaran', $total);
        $set('total_harga_barang', $total);

        $dibayar = is_numeric($get('dibayar')) ? floatval($get('dibayar')) : 0;
        $kembalian = $dibayar - $total;
        $set('kembalian', $kembalian);

        // DEBUG: Log untuk debugging (hapus setelah fix)
        Log::info('UpdateTotals called', [
            'total_harga_barang' => $total,
            'items_count' => is_array($items) ? count($items) : 0,
            'dibayar' => $dibayar,
            'kembalian' => $kembalian
        ]);
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
                        return $record->barangs->pluck('nama_barang')->implode(', ');
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('barangs', function ($q) use ($search) {
                            $q->where('nama_barang', 'like', "%{$search}%");
                        });
                    })
                    ->limit(40)
                    ->tooltip(function ($record) {
                        return $record->barangs->pluck('nama_barang')->implode("\n");
                    }),
                Tables\Columns\TextColumn::make('total_harga_barang')
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
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('kasir', 'name')
                    ->searchable(),

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
        return [];
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
