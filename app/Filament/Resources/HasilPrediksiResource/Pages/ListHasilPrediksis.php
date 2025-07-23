<?php

namespace App\Filament\Resources\HasilPrediksiResource\Pages;

use App\Filament\Resources\HasilPrediksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHasilPrediksis extends ListRecords
{
    protected static string $resource = HasilPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
