<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use HasFactory;

    protected $fillable = ['nama_barang', 'harga_barang', 'jumlah_stok'];

    public function transaksis(): BelongsToMany
    {
        return $this->belongsToMany(Transaksi::class, 'barang_transaksi')
            ->withPivot('jumlah', 'harga_satuan')
            ->withTimestamps();
    }

    public function prosesPrediksis(): HasMany
    {
        return $this->hasMany(ProsesPrediksi::class);
    }
    public function prediksi(): HasMany
    {
        return $this->hasMany(Prediksi::class);
    }
}
