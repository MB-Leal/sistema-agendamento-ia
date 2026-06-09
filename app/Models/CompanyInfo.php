<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{
    use HasFactory;

    // Nome da tabela (opcional se seguir o padrão, mas bom para garantir)
    protected $table = 'company_infos';

    // Campos que permitimos salvar via updateOrCreate ou create
    protected $fillable = [
        'nome_fantasia',
        'cnpj',
        'whatsapp_suporte',
        'email_contato',
        'cep',
        'logradouro',
        'numero',
        'bairro',
        'cidade',
        'estado',
    ];
}