<?php

namespace App\Support;

use App\Enums\JobApplicationStatus;
use Illuminate\Support\Collection;

/**
 * Filtros da lista de vagas ATS (trail + hub documentos): query string `jd_list_filter`.
 */
final class AgentsDocumentTrailListFilter
{
    /** Activas e não terminadas (exclui Não prosseguiu / Aceite). */
    public const OPEN = 'open';

    /** Todas as vagas activas (qualquer estado de candidatura). */
    public const ACTIVE_ALL = 'active_all';

    /** Activas com outcome terminal (Aceite / Não prosseguiu). */
    public const CLOSED = 'closed';

    /** Arquivadas (is_active = false). */
    public const INACTIVE = 'inactive';

    public static function fromQuery(?string $value): string
    {
        return match ($value) {
            self::ACTIVE_ALL, self::CLOSED, self::INACTIVE => $value,
            default => self::OPEN,
        };
    }

    /**
     * @param  Collection<int, \App\Models\AgentDocument>  $activeJds
     * @return Collection<int, \App\Models\AgentDocument>
     */
    public static function filterActiveJds(Collection $activeJds, string $mode): Collection
    {
        return match ($mode) {
            self::OPEN => $activeJds->filter(function ($jd): bool {
                $st = $jd->application_status;

                return $st === null || ! $st->isTerminal();
            })->values(),
            self::CLOSED => $activeJds->filter(function ($jd): bool {
                $st = $jd->application_status;

                return $st !== null && $st->isTerminal();
            })->values(),
            default => $activeJds->values(),
        };
    }

    public static function isInactiveMode(string $mode): bool
    {
        return $mode === self::INACTIVE;
    }

    /** Valor atual do select da combo (inactive ou valor do enum JobApplicationStatus). */
    public static function listStatusKey(bool $isActive, ?JobApplicationStatus $applicationStatus): string
    {
        if (! $isActive) {
            return 'inactive';
        }

        return ($applicationStatus ?? JobApplicationStatus::Draft)->value;
    }
}
