<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WhatsAppService
{
    /**
     * Consulta as quadras e horários livres para uma data específica.
     */
    public function consultarDisponibilidade($dataString)
    {
        try {
            // Converte formatos comuns (ex: "25/05/2026" ou "amanhã") para Y-m-d se necessário
            $data = Carbon::parse(str_replace('/', '-', $dataString))->format('Y-m-d');
        } catch (\Exception $e) {
            return "Não consegui entender a data fornecida. Use o formato DD/MM/AAAA.";
        }

        // 1. Busca todos os horários cadastrados no sistema
        // Ajuste os nomes de tabela/coluna se forem diferentes no seu dump local
        $horariosDisponiveis = DB::table('horarios')->get();

        // 2. Busca reservas que já estão ocupadas para aquele dia
        $reservasOcupadas = DB::table('reservas')
            ->whereDate('data', $data)
            ->whereIn('status', ['confirmado', 'pago'])
            ->pluck('horario_id')
            ->toArray();

        $textoOpcoes = "🏟️ Horários livres para o dia " . Carbon::parse($data)->format('d/m/Y') . ":\n\n";
        $encontrouLivres = false;

        foreach ($horariosDisponiveis as $horario) {
            if (!in_array($horario->id, $reservasOcupadas)) {
                $textoOpcoes .= "🕒 Código [{$horario->id}] - Hora: {$horario->hora}\n";
                $encontrouLivres = true;
            }
        }

        if (!$encontrouLivres) {
            return "Infelizmente todos os horários estão esgotados para o dia " . Carbon::parse($data)->format('d/m/Y') . ". 😔";
        }

        return $textoOpcoes;
    }
}
