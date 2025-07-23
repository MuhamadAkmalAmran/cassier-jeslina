<?php

namespace App\Filament\Resources\TransaksiResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BarangsRelationManager extends RelationManager
{
    protected static string $relationship = 'barangs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_barang')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_barang')
            ->columns([
                Tables\Columns\TextColumn::make('nama_barang'),

                Tables\Columns\TextColumn::make('pivot.jumlah')
                    ->label('Jumlah'),

                Tables\Columns\TextColumn::make('pivot.harga_satuan')
                    ->label('Harga Satuan')
                    ->money('IDR'),

                // Membuat kolom kalkulasi untuk subtotal
                Tables\Columns\TextColumn::make('subtotal')
                    ->state(function ($record) {
                        // $record di sini adalah model Barang yang memiliki data pivot
                        return $record->pivot->jumlah * $record->pivot->harga_satuan;
                    })
                    ->money('IDR'),

            ])
            ->filters([
                //
            ]);
            // ->headerActions([
            //     Tables\Actions\CreateAction::make(),
            // ])
            // ->actions([
            //     Tables\Actions\EditAction::make(),
            //     Tables\Actions\DeleteAction::make(),
            // ])
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
    }
}
