<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use App\Models\Barang;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaksi extends EditRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    /**
     * Method ini dijalankan SEBELUM form diisi dengan data.
     * Kita gunakan untuk memuat data dari relasi (items & pembayaran).
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 1. Ambil item-item dari tabel pivot
        $items = [];
        foreach ($this->record->barangs as $barang) {
            $items[] = [
                'barang_id'    => $barang->id,
                'jumlah'       => $barang->pivot->jumlah,
                'harga_satuan' => $barang->pivot->harga_satuan,
            ];
        }
        $data['items'] = $items;

        // 2. Ambil data dari tabel pembayaran
        if ($this->record->pembayaran) {
            $data['dibayar']   = $this->record->pembayaran->dibayar;
            $data['kembalian'] = $this->record->pembayaran->kembalian;
        }

        return $data;
    }

    /**
     * Method ini dijalankan SEBELUM data yang diedit disimpan ke DB.
     * Kita hitung ulang total harga.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $total = 0;
        if (is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $total += ($item['jumlah'] ?? 0) * ($item['harga_satuan'] ?? 0);
            }
        }
        $data['total_harga_barang'] = $total;

        return $data;
    }

    /**
     * Method ini dijalankan SETELAH record transaksi utama diperbarui.
     * Kita perbarui data di tabel pivot dan pembayaran.
     */
    protected function afterSave(): void
    {
        // 1. Dapatkan daftar item LAMA sebelum di-sync
        $oldItems = $this->record->barangs()->get();
        foreach ($oldItems as $oldItem) {
            $barang = Barang::find($oldItem->id);
            if ($barang) {
                // Kembalikan stok lama
                $barang->increment('jumlah_stok', $oldItem->pivot->jumlah);
            }
        }
        // 1. Perbarui item-item di tabel pivot 'barang_transaksi'
        $itemsToSync = [];
        foreach ($this->data['items'] as $item) {
            $itemsToSync[$item['barang_id']] = [
                'jumlah'       => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan']
            ];
        }
        // sync() secara cerdas akan menambah, mengubah, atau menghapus item yang ada.
        $this->record->barangs()->sync($itemsToSync);

        foreach ($this->data['items'] as $item) {
            $barang = Barang::find($item['barang_id']);
            if ($barang) {
                // Kurangi stok baru
                $barang->decrement('jumlah_stok', $item['jumlah']);
            }
        }

        // 2. Perbarui atau buat data di tabel 'pembayarans'
        $this->record->pembayaran()->updateOrCreate(
            ['transaksi_id' => $this->record->id], // Kondisi untuk mencari
            [ // Data untuk diperbarui atau dibuat
                'total_pembayaran' => $this->record->total_harga_barang,
                'dibayar'          => $this->data['dibayar'],
                'kembalian'        => $this->data['kembalian'],
            ]
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
