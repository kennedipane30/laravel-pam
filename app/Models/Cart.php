<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    // Relasi: Cart punya banyak items
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Relasi: Cart milik 1 user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper: Hitung total harga
    public function getTotalPrice()
    {
        return $this->items->sum(function ($item) {
            return $item->product->harga * $item->quantity;
        });
    }

    // Helper: Hitung total item
    public function getTotalItems()
    {
        return $this->items->sum('quantity');
    }
}
