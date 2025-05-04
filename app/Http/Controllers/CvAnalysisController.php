<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnthropicService;

class CvAnalysisController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'cv_text' => 'required|string',
            'job_description' => 'required|string',
        ]);

        try {
            $anthropicService = new AnthropicService();
            $analysis = $anthropicService->analyzeCvJob(
                $request->input('cv_text'),
                $request->input('job_description')
            );

            return response()->json([
                'success' => true,
                'analysis' => $analysis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao analisar CV: ' . $e->getMessage()
            ], 500);
        }
    }
    // Método para extrair texto de PDF
    private function extractTextFromPdf($file)
    {
        $path = $file->path();
        return \Spatie\PdfToText\Pdf::getText($path);
    }

    // Método para extrair texto de DOCX
    private function extractTextFromDocx($file)
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->path());
        $text = '';
        
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $element->getText() . ' ';
                    }
                }
            }
        }
        
        return $text;
    }
}