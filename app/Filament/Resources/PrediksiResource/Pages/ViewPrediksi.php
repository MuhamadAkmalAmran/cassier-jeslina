<?php

namespace App\Filament\Resources\PrediksiResource\Pages;

use App\Filament\Resources\HasilPrediksiResource;
use App\Filament\Resources\PrediksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrediksi extends ViewRecord
{
    protected static string $resource = HasilPrediksiResource::class;
}
