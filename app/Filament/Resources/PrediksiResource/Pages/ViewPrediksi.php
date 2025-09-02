<?php

namespace App\Filament\Resources\PrediksiResource\Pages;

use App\Filament\Resources\HasilPrediksiResource;
use App\Filament\Resources\PrediksiResource;
use App\Models\Prediksi;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrediksi extends ViewRecord
{
    protected static string $resource = PrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol "Kembali" baru
            Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray') // Beri warna agar tidak terlalu menonjol
                ->url($this->getResource()::getUrl('index')), // Arahkan ke halaman daftar
        ];
    }
}
