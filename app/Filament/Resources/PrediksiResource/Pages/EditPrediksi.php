<?php

namespace App\Filament\Resources\PrediksiResource\Pages;

use App\Filament\Resources\PrediksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrediksi extends EditRecord
{
    protected static string $resource = PrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
