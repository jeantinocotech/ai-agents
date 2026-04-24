<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordLoader;
use Smalot\PdfParser\Parser as PdfParser;

final class UserCvTextExtractor
{
    public static function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'txt') {
                return (string) file_get_contents($file->getRealPath());
            }

            if ($extension === 'pdf') {
                $parser = new PdfParser;
                $pdf = $parser->parseFile($file->getRealPath());

                return trim((string) $pdf->getText());
            }

            if (in_array($extension, ['docx', 'doc'], true)) {
                $extracted = '';
                $phpWord = WordLoader::load($file->getRealPath(), 'Word2007');

                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                            foreach ($element->getElements() as $subElement) {
                                if (method_exists($subElement, 'getText')) {
                                    $text = $subElement->getText();
                                    if (is_string($text)) {
                                        $extracted .= $text."\n";
                                    }
                                }
                            }
                        } elseif (method_exists($element, 'getText') && ! ($element instanceof \PhpOffice\PhpWord\Element\TextRun)) {
                            $text = $element->getText();
                            if (is_string($text)) {
                                $extracted .= $text."\n";
                            }
                        }
                    }
                }

                return trim($extracted);
            }
        } catch (\Throwable $e) {
            Log::warning('UserCvTextExtractor failed', ['error' => $e->getMessage(), 'ext' => $extension]);
        }

        return '';
    }
}
