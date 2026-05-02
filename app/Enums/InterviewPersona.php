<?php

namespace App\Enums;

enum InterviewPersona: string
{
    case ScreeningRh = 'screening_rh';

    case HiringManager = 'hiring_manager';

    case Technical = 'technical';

    case Peer = 'peer';

    case Executive = 'executive';

    case CultureFit = 'culture_fit';

    public function label(): string
    {
        return match ($this) {
            self::ScreeningRh => 'RH / Triagem inicial',
            self::HiringManager => 'Responsável pela contratação',
            self::Technical => 'Entrevista técnica',
            self::Peer => 'Colega / equipa',
            self::Executive => 'Executivo / direção',
            self::CultureFit => 'Cultura / valores',
        };
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
