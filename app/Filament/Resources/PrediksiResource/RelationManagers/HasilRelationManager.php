<?php

namespace App\Filament\Resources\PrediksiResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HasilRelationManager extends RelationManager
{
    protected static string $relationship = 'hasil';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('prediksi_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('tanggal')
        ->columns([
            // TAMPILKAN LANGSUNG DARI KOLOM DATABASE
            Tables\Columns\TextColumn::make('tanggal')
                ->label('Prediksi Untuk Bulan')
                ->date('F Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('penjualan_aktual')->numeric(),
            Tables\Columns\TextColumn::make('stok_aktual')->numeric(),
            Tables\Columns\TextColumn::make('prediksi_stok')->numeric()->weight('bold'),
        ])
        ->defaultSort('tanggal', 'desc')
        ->actions([])
        ->bulkActions([]);
}
}
