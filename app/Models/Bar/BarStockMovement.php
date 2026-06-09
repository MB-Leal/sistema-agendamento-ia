<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BarStockMovement extends Model
{
    protected $fillable = ['bar_product_id', 'user_id', 'quantity', 'type', 'description'];

    public function product()
    {
        return $this->belongsTo(BarProduct::class, 'bar_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
