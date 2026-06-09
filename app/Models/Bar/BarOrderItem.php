<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarOrderItem extends Model
{
    protected $table = 'bar_order_items';

    // 🚀 ADICIONE O 'subtotal' AQUI:
    protected $fillable = [
        'bar_order_id',
        'bar_product_id',
        'quantity',
        'unit_price',
        'subtotal' // 🚀 Adicione aqui!
    ];

    public function product()
    {
        return $this->belongsTo(BarProduct::class, 'bar_product_id');
    }

    // Adicionado para facilitar a navegação
    public function order()
    {
        return $this->belongsTo(BarOrder::class, 'bar_order_id');
    }
}
