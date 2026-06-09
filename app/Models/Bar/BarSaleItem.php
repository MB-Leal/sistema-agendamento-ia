<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarSaleItem extends Model
{
    use HasFactory;

    // Nome da tabela definido na migration
    protected $table = 'bar_sale_items';

    protected $fillable = [
        'bar_sale_id',
        'bar_product_id',
        'quantity',
        'price_at_sale'
    ];

    /**
     * Relacionamento: O item pertence a uma venda.
     */
    public function sale()
    {
        return $this->belongsTo(BarSale::class, 'bar_sale_id');
    }

    /**
     * Relacionamento: O item refere-se a um produto do estoque.
     */
    public function product()
    {
        return $this->belongsTo(BarProduct::class, 'bar_product_id');
    }
}
