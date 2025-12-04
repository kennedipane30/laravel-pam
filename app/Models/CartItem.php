<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'catatan'
    ];

    // Relasi: Item milik 1 cart
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Relasi: Item adalah 1 produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Helper: Subtotal item ini
    public function getSubtotal()
    {
        return $this->product->harga * $this->quantity;
    }
}
