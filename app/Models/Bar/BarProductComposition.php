<?php

namespace App\Models\Bar; // Certifique-se que o namespace reflete a pasta Bar

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarProductComposition extends Model
{
    protected $fillable = ['parent_id', 'child_id', 'quantity'];

    // Relacionamento com o produto "Filho" (o que compõe o combo)
    public function product(): BelongsTo
    {
        // Como o BarProduct está na mesma pasta, não precisa do caminho completo
        return $this->belongsTo(BarProduct::class, 'child_id');
    }
}
