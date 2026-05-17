<?php

namespace App\Rules;

use App\Support\UploadLimits;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Valida fotos de perfil aceitando PNG/JPG/WEBP/GIF reais (incl. image/x-png).
 */
class ValidProfilePhoto implements ValidationRule
{
    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/webp',
        'image/gif',
    ];

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $maxBytes = UploadLimits::profilePhotoMaxBytes();
        $maxLabel = UploadLimits::profilePhotoMaxLabel();

        if (! $value->isValid()) {
            $fail($this->uploadErrorMessage($value, $maxLabel));

            return;
        }

        if ($value->getSize() > $maxBytes) {
            $fail("A foto pode ter no máximo {$maxLabel}.");

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $clientMime = strtolower((string) $value->getMimeType());
        $detectedMime = $this->detectMimeType($value->getPathname());

        $mimeCandidates = array_filter([$clientMime, $detectedMime]);
        $mimeOk = $this->matchesAllowedMime($mimeCandidates);
        $extOk = in_array($extension, self::ALLOWED_EXTENSIONS, true);

        if (! $mimeOk && ! $extOk) {
            $fail('A foto deve ser JPG, PNG, WEBP ou GIF.');

            return;
        }

        if (@getimagesize($value->getPathname()) === false && ! $mimeOk) {
            $fail('Não foi possível ler esta imagem. Tente exportar como JPG ou PNG padrão.');

            return;
        }
    }

    private function uploadErrorMessage(UploadedFile $file, string $maxLabel): string
    {
        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "A foto é grande demais para o servidor (limite atual: {$maxLabel}). "
                .'Reduza o tamanho do ficheiro, exporte como JPG ou peça ao administrador para aumentar upload_max_filesize no PHP.',
            UPLOAD_ERR_PARTIAL => 'O envio da foto foi interrompido. Tente novamente.',
            UPLOAD_ERR_NO_FILE => 'Nenhuma foto foi enviada.',
            default => "O envio da foto falhou. Use uma imagem até {$maxLabel}.",
        };
    }

    /**
     * @param  list<string>  $mimes
     */
    private function matchesAllowedMime(array $mimes): bool
    {
        foreach ($mimes as $mime) {
            if ($mime === '') {
                continue;
            }
            if (in_array($mime, self::ALLOWED_MIMES, true)) {
                return true;
            }
            if (str_starts_with($mime, 'image/') && in_array(
                substr($mime, 6),
                ['jpeg', 'png', 'x-png', 'webp', 'gif', 'pjpeg'],
                true
            )) {
                return true;
            }
        }

        return false;
    }

    private function detectMimeType(string $path): string
    {
        if (! is_readable($path)) {
            return '';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);

                return is_string($mime) ? strtolower($mime) : '';
            }
        }

        return '';
    }
}
