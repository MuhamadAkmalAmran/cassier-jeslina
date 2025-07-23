<?php

namespace App\Filament\Resources\HasilPrediksiResource\Pages;

use App\Filament\Resources\HasilPrediksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHasilPrediksi extends EditRecord
{
    protected static string $resource = HasilPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
