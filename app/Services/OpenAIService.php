<?php

namespace App\Services;

use OpenAI;
use App\Models\Reserva;
use App\Models\Horario;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        $apiKey = env('OPENAI_API_KEY');
        if ($apiKey) {
            $this->client = OpenAI::client($apiKey);
        }
    }

    public function getResponse($customerPhone, $messageHistory)
    {
        if (!$this->client) return "Sistema de IA temporariamente offline.";

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messageHistory,
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'verificar_disponibilidade',
                        'description' => 'Verifica horários disponíveis para agendamento em uma data específica',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'string', 'description' => 'Data no formato YYYY-MM-DD'],
                            ],
                            'required' => ['data'],
                        ],
                    ],
                ]
            ],
        ]);

        return $response;
    }
}
