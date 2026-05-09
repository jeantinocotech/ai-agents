<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\PageBreak;
use PhpOffice\PhpWord\Element\PreserveText;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory as WordLoader;
use Smalot\PdfParser\Parser as PdfParser;

final class UserCvTextExtractor
{
    /**
     * Arquivos .docx são ZIP (OOXML); o PhpWord usa ZipArchive.
     */
    public static function phpZipAvailableForDocx(): bool
    {
        return extension_loaded('zip') && class_exists(\ZipArchive::class);
    }

    public static function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath() ?: $file->getPathname();

        try {
            if ($extension === 'txt') {
                return trim((string) file_get_contents($path));
            }

            if ($extension === 'pdf') {
                $parser = new PdfParser;
                $pdf = $parser->parseFile($path);

                return trim((string) $pdf->getText());
            }

            if (in_array($extension, ['docx', 'doc'], true)) {
                if ($extension === 'docx' && ! self::phpZipAvailableForDocx()) {
                    Log::warning('UserCvTextExtractor: DOCX requires PHP zip extension (e.g. php-zip package on the server).');

                    return '';
                }

                $reader = $extension === 'doc' ? 'MsDoc' : 'Word2007';
                $phpWord = WordLoader::load($path, $reader);
                $extracted = self::plainTextFromPhpWord($phpWord);

                if ($extracted === '' && $file->getSize() > 0) {
                    Log::warning('UserCvTextExtractor: Word file produced no plain text', [
                        'ext' => $extension,
                        'reader' => $reader,
                        'bytes' => $file->getSize(),
                    ]);
                }

                return $extracted;
            }
        } catch (\Throwable $e) {
            Log::warning('UserCvTextExtractor failed', [
                'error' => $e->getMessage(),
                'ext' => $extension,
                'path_usable' => is_readable($path),
            ]);
        }

        return '';
    }

    private static function plainTextFromPhpWord(\PhpOffice\PhpWord\PhpWord $phpWord): string
    {
        $out = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                self::appendPlainTextFromWordElement($element, $out);
            }
        }

        return trim($out);
    }

    private static function appendPlainTextFromWordElement(AbstractElement $element, string &$out): void
    {
        if ($element instanceof TextBreak) {
            $out .= "\n";

            return;
        }

        if ($element instanceof PageBreak) {
            $out .= "\n";

            return;
        }

        if ($element instanceof Title) {
            $t = $element->getText();
            if ($t instanceof TextRun) {
                self::appendPlainTextFromWordElement($t, $out);
            } elseif (is_string($t) && $t !== '') {
                $out .= $t."\n";
            }
            $out .= "\n";

            return;
        }

        if ($element instanceof TextRun) {
            foreach ($element->getElements() as $child) {
                self::appendPlainTextFromWordElement($child, $out);
            }

            return;
        }

        if ($element instanceof PreserveText) {
            $raw = $element->getText();
            if (is_array($raw)) {
                foreach ($raw as $part) {
                    if (! is_string($part) || $part === '') {
                        continue;
                    }
                    if (str_starts_with($part, '{') && str_contains($part, '}')) {
                        continue;
                    }
                    $out .= $part;
                }
                $out .= "\n";
            } elseif (is_string($raw) && $raw !== '') {
                $out .= $raw."\n";
            }

            return;
        }

        if ($element instanceof ListItem) {
            $depth = max(0, (int) $element->getDepth());
            $out .= str_repeat('  ', $depth);
            $textObject = $element->getTextObject();
            if ($textObject instanceof Text) {
                $tx = $textObject->getText();
                if (is_string($tx) && $tx !== '') {
                    $out .= $tx."\n";
                }
            }

            return;
        }

        if ($element instanceof Text) {
            $text = $element->getText();
            if (is_string($text) && $text !== '') {
                $out .= $text."\n";
            }

            return;
        }

        if ($element instanceof Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        self::appendPlainTextFromWordElement($cellElement, $out);
                    }
                }
                $out .= "\n";
            }

            return;
        }

        if ($element instanceof AbstractContainer) {
            foreach ($element->getElements() as $child) {
                self::appendPlainTextFromWordElement($child, $out);
            }

            return;
        }

        if (method_exists($element, 'getText')) {
            /** @var mixed $text */
            $text = $element->getText();
            if ($text instanceof TextRun) {
                self::appendPlainTextFromWordElement($text, $out);
                $out .= "\n";
            } elseif (is_string($text) && $text !== '') {
                $out .= $text."\n";
            }
        }
    }
}
