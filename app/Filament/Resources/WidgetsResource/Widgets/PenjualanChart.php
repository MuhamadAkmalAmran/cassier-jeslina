<?php

namespace App\Filament\Widgets;

use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PenjualanChart extends ChartWidget
{
    // Judul yang akan tampil di atas chart
    protected static ?string $heading = 'Grafik Penjualan (12 Bulan Terakhir)';

    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 12, // mobile = full
            'md' => 12,      // tablet = full
            'lg' => 12,      // desktop = full
        ];
    }

    /**
     * Method utama untuk mengambil dan memformat data untuk chart.
     */
    protected function getData(): array
    {
        // 1. Ambil data dari model Transaksi dari 12 bulan terakhir
        $data = Transaksi::query()
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_harga_barang) as total')
            )
            ->where('created_at', '>=', now()->subMonths(1)->startOfYear())
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // 2. Jika tidak ada data, kembalikan array kosong agar tidak error
        if ($data->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // 3. Siapkan data untuk ditampilkan di chart
        $labels = $data->map(function ($item) {
            // Format label bulan, contoh: "Sep 2025"
            return Carbon::createFromDate($item->year, $item->month)->translatedFormat('M');
        });

        $dataset = $data->map(function ($item) {
            return $item->total;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $dataset,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Total Penjualan (IDR)', // Label untuk sumbu Y
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Bulan', // Label untuk sumbu X
                    ],
                ],
            ],
        ];
    }

    /**
     * Tentukan tipe chart yang akan digunakan.
     */
    protected function getType(): string
    {
        return 'line';
    }
}
