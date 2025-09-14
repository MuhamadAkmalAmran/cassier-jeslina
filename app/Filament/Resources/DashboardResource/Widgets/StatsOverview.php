<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Models\Barang;
use App\Models\Transaksi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 12, // mobile = full
            'md' => 12,      // tablet = full
            'lg' => 12,      // desktop = full
        ];
    }

    protected function getStats(): array
    {
        // Menghitung total pendapatan khusus bulan ini
        $totalPendapatanBulanIni = Transaksi::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_harga_barang');

        return [
            // Kartu Statistik 1: Jumlah Barang
            Stat::make('Jumlah Barang', Barang::count())
                ->description('Total jenis barang yang tersedia')
                ->icon('heroicon-o-archive-box')
                ->color('success'),

            // Kartu Statistik 2: Jumlah Transaksi
            Stat::make('Jumlah Transaksi', Transaksi::count())
                ->description('Total semua transaksi yang tercatat')
                ->icon('heroicon-o-shopping-cart')
                ->color('info'),

            // Kartu Statistik 3: Total Pendapatan Bulan Ini
            Stat::make('Total Pendapatan (Bulan Ini)', 'Rp ' . number_format($totalPendapatanBulanIni, 0, ',', '.'))
                ->description('Total pendapatan dari semua transaksi bulan ini')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
