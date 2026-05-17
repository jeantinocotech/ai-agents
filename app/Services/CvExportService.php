<?php

namespace App\Services;

use App\Models\UserCv;
use App\Support\CvExportFilename;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Text as PhpWordText;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CvExportService
{
    /**
     * @return StreamedResponse|\Illuminate\Http\Response
     */
    public function download(UserCv $userCv, string $format)
    {
        return match ($format) {
            'pdf' => $this->downloadPdf($userCv),
            'docx' => $this->downloadDocx($userCv),
            default => abort(404),
        };
    }

    private function downloadPdf(UserCv $userCv)
    {
        $filename = CvExportFilename::build($userCv, 'pdf');

        return Pdf::loadView('exports.cv-pdf', [
            'title' => trim((string) $userCv->title) ?: 'Curriculum',
            'body' => (string) $userCv->body,
        ])->download($filename);
    }

    private function downloadDocx(UserCv $userCv): BinaryFileResponse
    {
        $filename = CvExportFilename::build($userCv, 'docx');
        $tempPath = tempnam(sys_get_temp_dir(), 'cv_export_');
        if ($tempPath === false) {
            abort(500, 'Não foi possível preparar a exportação DOCX.');
        }

        $docxPath = $tempPath.'.docx';
        if (! rename($tempPath, $docxPath)) {
            @unlink($tempPath);
            abort(500, 'Não foi possível preparar a exportação DOCX.');
        }

        try {
            Settings::setOutputEscapingEnabled(true);

            $phpWord = new PhpWord;
            $section = $phpWord->addSection();
            $section->addTitle($this->docxLine(trim((string) $userCv->title) ?: 'Curriculum'), 1);
            $section->addTextBreak(1);

            $body = str_replace(["\r\n", "\r"], "\n", (string) $userCv->body);
            foreach (explode("\n", $body) as $line) {
                $trimmed = rtrim($line);
                if ($trimmed === '') {
                    $section->addTextBreak(1);

                    continue;
                }
                $section->addText($this->docxLine($trimmed));
            }

            IOFactory::createWriter($phpWord, 'Word2007')->save($docxPath);
        } catch (\Throwable $e) {
            @unlink($docxPath);
            throw $e;
        }

        return response()->download($docxPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    private function docxLine(string $text): string
    {
        $text = str_replace("\0", '', $text);

        return PhpWordText::controlCharacterPHP2OOXML($text);
    }
}
