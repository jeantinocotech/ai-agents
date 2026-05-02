<?php

namespace App\Enums;

enum InterviewApplicationOutcome: string
{
    /** Processo sem resultado final positivo definido pelo candidato e sem rejeição ou desistência na trilha. */
    case Ongoing = 'ongoing';

    case Approved = 'approved';

    /** Rejeição ou desistência em pelo menos uma ronda (detalhe na ronda). */
    case DidNotProceed = 'did_not_proceed';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Em curso',
            self::Approved => 'Aprovado',
            self::DidNotProceed => 'Não prosseguiu',
        };
    }

    /**
     * Filtros iniciais da listagem de entrevistas: esconder processos já encerrados negativamente.
     *
     * @return list<string>
     */
    public static function defaultListingFilters(): array
    {
        return [self::Ongoing->value, self::Approved->value];
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
