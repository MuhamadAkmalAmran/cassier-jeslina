<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use App\Models\Barang;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $total = 0;

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $qty   = (float) ($item['jumlah'] ?? 0);
                $price = (float) ($item['harga_satuan'] ?? 0);
                $total += $qty * $price;
            }
        }

        // Penting: set kolom yang akan disimpan ke tabel transaksis
        $data['total_harga_barang'] = $total;

        return $data;
    }

    protected function afterCreate(): void
    {

        if (empty($this->record->total_harga_barang)) {
            $this->record->forceFill([
                'total_harga_barang' => (float) ($this->data['total_harga_barang'] ?? 0),
            ])->save();
        }
        DB::transaction(function () {
            // 1) Simpan pivot items
            $itemsToSync = [];
            foreach ($this->data['items'] as $item) {
                $itemsToSync[$item['barang_id']] = [
                    'jumlah'       => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                ];
            }
            $this->record->barangs()->sync($itemsToSync);

            // 2) Pembayaran: gunakan nilai dari record agar konsisten
            $this->record->pembayaran()->create([
                'total_pembayaran' => $this->record->total_harga_barang,
                'dibayar'          => (float) ($this->data['dibayar'] ?? 0),
                'kembalian'        => (float) ($this->data['kembalian'] ?? 0),
            ]);

            // 3) Kurangi stok
            foreach ($this->data['items'] as $item) {
                $barang = Barang::find($item['barang_id']);
                if ($barang) {
                    $barang->decrement('jumlah_stok', (int) $item['jumlah']);
                }
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
