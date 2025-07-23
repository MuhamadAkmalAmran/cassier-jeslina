<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends Model
{
    use HasFactory;

        protected $fillable = ['user_id', 'total_harga_barang'];

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function barangs(): BelongsToMany
    {
        return $this->belongsToMany(Barang::class, 'barang_transaksi')
                    ->withPivot('jumlah', 'harga_satuan')
                    ->withTimestamps();
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class);
    }
}
