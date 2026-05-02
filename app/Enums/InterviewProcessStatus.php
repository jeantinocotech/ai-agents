<?php

namespace App\Enums;

enum InterviewProcessStatus: string
{
    /** Continua ou aguardou feedback nesta etapa do processo. */
    case InProcess = 'in_process';

    /** Avançou (passou esta ronda / segue nas etapas). */
    case Advanced = 'advanced';

    /** Esta ronda / etapa ficou pelo lado da empresa ou do processo (não houve seguimento). */
    case Rejected = 'rejected';

    /** Desistência do candidato. */
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::InProcess => 'Em processo',
            self::Advanced => 'Avançou',
            self::Rejected => 'Não prosseguiu',
            self::Withdrawn => 'Desistiu',
        };
    }

    /**
     * Estados usados ao abrir o ecrã de listagem: processos «activos» (em curso ou com avanço).
     *
     * @return list<string>
     */
    public static function activeListingDefaults(): array
    {
        return [self::InProcess->value, self::Advanced->value];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function optionsForForms(): array
    {
        return array_map(fn (self $c) => [
            'value' => $c->value,
            'label' => $c->label(),
        ], self::cases());
    }
}
