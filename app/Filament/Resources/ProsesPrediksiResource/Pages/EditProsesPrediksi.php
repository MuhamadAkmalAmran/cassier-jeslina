<?php

namespace App\Filament\Resources\ProsesPrediksiResource\Pages;

use App\Filament\Resources\ProsesPrediksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProsesPrediksi extends EditRecord
{
    protected static string $resource = ProsesPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
