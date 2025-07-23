<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProsesPrediksiResource\Pages;
use App\Filament\Resources\ProsesPrediksiResource\RelationManagers;
use App\Models\ProsesPrediksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProsesPrediksiResource extends Resource
{
    protected static ?string $model = ProsesPrediksi::class;
    protected static bool $shouldRegisterNavigation = false;


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Hanya untuk Admin
    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('periode_prediksi')
                    ->label('Periode Data'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Prediksi')
                    ->date('F Y') // Format menjadi "Juli 2025"
                    ->sortable(),
                Tables\Columns\TextColumn::make('prediksi_stok')
                    ->label('Hasil Prediksi Penjualan'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProsesPrediksis::route('/'),
            'view' => Pages\ViewProsesPrediksi::route('/{record}'),
            // 'create' => Pages\CreateProsesPrediksi::route('/create'),
            // 'edit' => Pages\EditProsesPrediksi::route('/{record}/edit'),
        ];
    }
}
