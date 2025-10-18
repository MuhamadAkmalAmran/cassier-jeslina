<?php

namespace App\Filament\Resources\HasilPrediksiResource\Pages;

use App\Filament\Resources\HasilPrediksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHasilPrediksi extends ViewRecord
{
    protected static string $resource = HasilPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol "Kembali" baru
            Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray') // Beri warna agar tidak terlalu menonjol
                ->url($this->getResource()::getUrl('index')), // Arahkan ke halaman daftar

            // Tombol "Edit" bawaan (opsional, tapi best practice)
            Actions\EditAction::make(),
        ];
    }
}
