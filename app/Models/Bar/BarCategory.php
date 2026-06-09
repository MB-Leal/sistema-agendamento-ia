<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarCategory extends Model
{
    protected $table = 'bar_categories'; // Mantém o padrão
    protected $fillable = ['name'];
}
