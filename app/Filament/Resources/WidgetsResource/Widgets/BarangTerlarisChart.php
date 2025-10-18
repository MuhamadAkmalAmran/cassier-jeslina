<?php

namespace App\Filament\Widgets;

use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BarangTerlarisChart extends ChartWidget
{
    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 12,
            'md' => 12,
            'lg' => 12,
        ];
    }

    public function getHeading(): string
    {
        $bulan = now()->month;
        $tahun = now()->year;
        $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

        return "Barang Terjual - {$namaBulan} {$tahun}";
    }

    protected function getData(): array
    {
        $selectedMonth = now()->month;
        $selectedYear = now()->year;

        $data = DB::table('barang_transaksi')
            ->join('barangs', 'barang_transaksi.barang_id', '=', 'barangs.id')
            ->select('barangs.nama_barang', DB::raw('SUM(barang_transaksi.jumlah) as total_terjual'))
            ->whereYear('barang_transaksi.created_at', $selectedYear)
            ->whereMonth('barang_transaksi.created_at', $selectedMonth)
            ->groupBy('barangs.id', 'barangs.nama_barang')
            ->orderByDesc('total_terjual')
            ->get();

        if ($data->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }

        return [
            'datasets' => [[
                'label' => 'Total Terjual',
                'data' => $data->pluck('total_terjual'),
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                ],
                'borderColor' => [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                ],
                'borderWidth' => 1,
            ]],
            'labels' => $data->pluck('nama_barang'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'title' => ['display' => true, 'text' => 'Jumlah Unit Terjual'],
                ],
                'x' => [
                    'title' => ['display' => true, 'text' => 'Nama Barang'],
                ],
            ],
        ];
    }
}
