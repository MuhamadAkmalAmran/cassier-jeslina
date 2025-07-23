@php
    // Ambil record utama yang sedang dilihat
    $currentRecord = $getRecord();

    // Ambil semua data prediksi lain yang memiliki barang_id DAN periode_prediksi yang sama
    $history = \App\Models\ProsesPrediksi::where('barang_id', $currentRecord->barang_id)
        ->where('periode_prediksi', $currentRecord->periode_prediksi)
        ->orderBy('tanggal', 'desc')
        ->get();
@endphp

{{-- Komponen Card bawaan Filament untuk tampilan yang konsisten --}}
<x-filament::section>
    <x-slot name="heading">
        Riwayat Prediksi (Periode {{ $currentRecord->periode_prediksi }})
    </x-slot>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Barang</th>
                    <th scope="col" class="px-6 py-3">Prediksi Untuk Bulan</th>
                    <th scope="col" class="px-6 py-3 text-right">Penjualan Aktual</th>
                    <th scope="col" class="px-6 py-3 text-right">Stok Aktual</th>
                    <th scope="col" class="px-6 py-3 text-right">Prediksi Stok</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $prediction)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $prediction->barang->nama_barang }}
                        </th>
                        <td class="px-6 py-4">
                            {{ \Carbon\Carbon::parse($prediction->tanggal)->format('F Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ $prediction->penjualan_aktual }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ $prediction->stok_aktual }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold">
                            {{ $prediction->prediksi_stok }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center">Belum ada riwayat prediksi lain.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::section>
