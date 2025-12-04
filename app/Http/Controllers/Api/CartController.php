<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // 1. Lihat Keranjang
    public function index()
    {
        $user = Auth::user();

        // Ambil atau buat cart baru
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Load items dengan produk dan umkm
        $cart->load(['items.product.umkm']);

        return response()->json([
            'cart_id' => $cart->id,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'nama_produk' => $item->product->nama_produk,
                    'harga' => $item->product->harga,
                    'gambar' => $item->product->gambar,
                    'quantity' => $item->quantity,
                    'catatan' => $item->catatan,
                    'stok' => $item->product->stok,
                    'subtotal' => $item->product->harga * $item->quantity,
                    'umkm' => [
                        'id' => $item->product->umkm->id,
                        'nama_usaha' => $item->product->umkm->nama_usaha ?? 'Mitra UMKM'
                    ]
                ];
            }),
            'total_items' => $cart->getTotalItems(),
            'total_price' => $cart->getTotalPrice()
        ]);
    }

    // 2. Tambah ke Keranjang
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $product = Product::find($request->product_id);

        // Cek stok
        if ($product->stok < $request->quantity) {
            return response()->json(['message' => 'Stok tidak mencukupi'], 400);
        }

        // Ambil atau buat cart
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Cek apakah produk sudah ada di cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            // Update quantity jika sudah ada
            $newQuantity = $cartItem->quantity + $request->quantity;

            if ($product->stok < $newQuantity) {
                return response()->json(['message' => 'Stok tidak mencukupi'], 400);
            }

            $cartItem->update([
                'quantity' => $newQuantity,
                'catatan' => $request->catatan ?? $cartItem->catatan
            ]);

            $message = 'Quantity berhasil ditambah';
        } else {
            // Buat item baru
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'catatan' => $request->catatan
            ]);

            $message = 'Produk berhasil ditambahkan ke keranjang';
        }

        $cartItem->load('product');

        return response()->json([
            'message' => $message,
            'cart_item' => $cartItem,
            'cart_total_items' => $cart->getTotalItems()
        ], 201);
    }

    // 3. Update Quantity
    public function updateQuantity(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Keranjang tidak ditemukan'], 404);
        }

        $cartItem = CartItem::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        // Cek stok
        if ($cartItem->product->stok < $request->quantity) {
            return response()->json(['message' => 'Stok tidak mencukupi'], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'message' => 'Quantity berhasil diupdate',
            'cart_item' => $cartItem,
            'cart_total_items' => $cart->getTotalItems(),
            'cart_total_price' => $cart->getTotalPrice()
        ]);
    }

    // 4. Hapus Item
    public function removeItem($itemId)
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Keranjang tidak ditemukan'], 404);
        }

        $cartItem = CartItem::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Item berhasil dihapus',
            'cart_total_items' => $cart->getTotalItems(),
            'cart_total_price' => $cart->getTotalPrice()
        ]);
    }

    // 5. Clear Cart (Hapus semua item)
    public function clearCart()
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Keranjang kosong'], 404);
        }

        $cart->items()->delete();

        return response()->json(['message' => 'Keranjang berhasil dikosongkan']);
    }

    // 6. Update Catatan Item
    public function updateNote(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'catatan' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Keranjang tidak ditemukan'], 404);
        }

        $cartItem = CartItem::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        $cartItem->update(['catatan' => $request->catatan]);

        return response()->json([
            'message' => 'Catatan berhasil diupdate',
            'cart_item' => $cartItem
        ]);
    }
}
