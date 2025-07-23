<?php

namespace App\Observers;

use App\Models\Barang;
use App\Models\Transaksi;

class TransaksiObserver
{
    /**
     * Handle the Transaksi "created" event.
     */
    public function created(Transaksi $transaksi): void
    {
        $totalHarga = 0;
        // Loop melalui item barang di transaksi
        foreach ($transaksi->barangs as $item) {
            // Kurangi stok barang
            $barang = Barang::find($item->pivot->barang_id);
            $barang->jumlah_stok -= $item->pivot->jumlah;
            $barang->save();

            $totalHarga += $item->pivot->harga_satuan * $item->pivot->jumlah;
        }

        // Update total harga di transaksi
        $transaksi->total_harga_barang = $totalHarga;
        $transaksi->saveQuietly(); // saveQuietly agar tidak memicu observer lagi
    }

    /**
     * Handle the Transaksi "updated" event.
     */
    public function updated(Transaksi $transaksi): void
    {
        //
    }

    /**
     * Handle the Transaksi "deleted" event.
     */
    public function deleted(Transaksi $transaksi): void
    {
        foreach ($transaksi->barangs as $item) {
            $barang = Barang::find($item->id);
            if ($barang) {
                // Gunakan increment untuk menambah stok kembali
                $barang->increment('jumlah_stok', $item->pivot->jumlah);
            }
        }
    }

    /**
     * Handle the Transaksi "restored" event.
     */
    public function restored(Transaksi $transaksi): void
    {
        //
    }

    /**
     * Handle the Transaksi "force deleted" event.
     */
    public function forceDeleted(Transaksi $transaksi): void
    {
        //
    }
}
