<?php

namespace App\Enums;

enum JobApplicationStatus: string
{
    /** Vaga sem CV de perfil associado, ou com CV mas ainda sem envio ATS registado. */
    case Draft = 'draft';

    /** CV+vaga enviados ao assistente ATS (alinhamento ATS registado). */
    case Submitted = 'submitted';

    /** CV enviado à empresa; à espera de retorno (entrevista ou não prosseguiu). */
    case CvSent = 'cv_sent';

    /** Existe processo de entrevista ou rondas em curso. */
    case Interviewing = 'interviewing';

    /** Processo encerrado sem sucesso (pré ou pós-entrevista). */
    case DidNotProceed = 'did_not_proceed';

    /** Candidatura aprovada (alinhado a InterviewApplicationOutcome::Approved). */
    case Accepted = 'accepted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Em preparação',
            self::Submitted => 'Alinhamento ATS',
            self::CvSent => 'CV enviado à empresa',
            self::Interviewing => 'Entrevistas em curso',
            self::DidNotProceed => 'Não prosseguiu',
            self::Accepted => 'Aceite',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Accepted || $this === self::DidNotProceed;
    }

    /** Tabela ATS no workspace: só em preparação ou após alinhamento no assistente. */
    public function allowsAtsTableWorkspace(): bool
    {
        return $this === self::Draft || $this === self::Submitted;
    }
}
