<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use App\Models\Barang;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Hitung total belanja dari items dan siapkan untuk disimpan
        $total = 0;
        if (is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $total += ($item['jumlah'] ?? 0) * ($item['harga_satuan'] ?? 0);
            }
        }
        $data['total_harga_barang'] = $total;

        return $data;
    }

    protected function afterCreate(): void
    {
        // 1. Simpan item-item ke tabel pivot 'barang_transaksi'
        $itemsToSync = [];
        foreach ($this->data['items'] as $item) {
            $itemsToSync[$item['barang_id']] = [
                'jumlah'       => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan']
            ];
            $barang = Barang::find($item['barang_id']);
            if ($barang) {
                // Gunakan decrement untuk operasi yang aman
                $barang->decrement('jumlah_stok', $item['jumlah']);
            }
        }
        $this->record->barangs()->sync($itemsToSync);

        // 2. Simpan detail pembayaran ke tabel 'pembayarans'
        $this->record->pembayaran()->create([
            'total_pembayaran' => $this->record->total_harga_barang,
            'dibayar'          => $this->data['dibayar'],
            'kembalian'        => $this->data['kembalian'],
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
