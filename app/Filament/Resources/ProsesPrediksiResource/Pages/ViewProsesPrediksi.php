<?php

namespace App\Filament\Resources\ProsesPrediksiResource\Pages;

use App\Filament\Resources\ProsesPrediksiResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProsesPrediksi extends ViewRecord
{
    protected static string $resource = ProsesPrediksiResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            // Tampilkan komponen ViewEntry yang memuat tabel riwayat
            ViewEntry::make('history')
                ->view('filament.infolists.components.prediction-history-list')
                ->columnSpanFull() // Pastikan komponen ini menggunakan lebar penuh
        ]);
    }
}
