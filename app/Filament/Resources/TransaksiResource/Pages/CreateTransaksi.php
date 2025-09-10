<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use App\Models\Barang;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    /**
     * Method ini berjalan TEPAT SEBELUM data form disimpan ke database.
     * Kita akan menghitung total HANYA di sini sebagai satu-satunya sumber kebenaran.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $total = 0;
        // Pastikan 'items' ada dan merupakan array
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                // Kalkulasi total berdasarkan data yang dikirim dari form repeater
                $total += ($item['jumlah'] ?? 0) * ($item['harga_satuan'] ?? 0);
            }
        }

        // Secara paksa atur nilai total_harga_barang di sini.
        // Ini akan menimpa nilai apa pun yang mungkin dikirim dari hidden input.
        $data['total_harga_barang'] = $total;

        return $data;
    }

    /**
     * Method ini berjalan TEPAT SETELAH record Transaksi utama berhasil dibuat.
     */
    protected function afterCreate(): void
    {
        // '$this->record' adalah record Transaksi yang baru saja dibuat.
        // '$this->data' adalah data asli dari form.

        // 1. Simpan item-item ke tabel pivot 'barang_transaksi'

        $itemsToSync = [];
        foreach ($this->data['items'] as $item) {
            $itemsToSync[$item['barang_id']] = [
                'jumlah'       => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan']
            ];
        }

        // dd($this->data['total_harga_barang']);

        $this->record->barangs()->sync($itemsToSync);

        // 2. Buat record baru di tabel 'pembayarans'
        $this->record->pembayaran()->create([
            // Gunakan nilai total dari record yang sudah tersimpan untuk konsistensi
            'total_pembayaran' => $this->data['total_harga_barang'],
            'dibayar'          => $this->data['dibayar'],
            'kembalian'        => $this->data['kembalian'],
        ]);

        // 3. Logika untuk mengurangi stok barang
        foreach ($this->data['items'] as $item) {
            $barang = Barang::find($item['barang_id']);
            if ($barang) {
                $barang->decrement('jumlah_stok', $item['jumlah']);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        // Kembali ke daftar setelah berhasil membuat transaksi
        return $this->getResource()::getUrl('index');
    }
}
