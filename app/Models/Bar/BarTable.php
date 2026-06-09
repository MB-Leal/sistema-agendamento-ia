<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarTable extends Model
{
    protected $table = 'bar_tables';
    protected $fillable = ['identifier', 'status'];

    // Uma mesa pode ter vários pedidos ao longo do tempo
    public function orders()
    {
        return $this->hasMany(BarOrder::class);
    }
}
