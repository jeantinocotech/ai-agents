<?php

namespace App\Support;

use Illuminate\Http\Request;

final class ChatKitAtsPairContext
{
    /**
     * Títulos do CV e da vaga para exibir no chat ATS compacto (query ou seleção actual).
     *
     * @param  array{cvs: array<int, array{id: string, title: string}>, jds: array<int, array{id: int|string, title: string}>, defaults: array{cv_document_id?: mixed, jd_document_id?: mixed}}  $documentLibrary
     * @return array{cv_title: string, jd_title: string}|null
     */
    public static function fromLibrary(Request $request, array $documentLibrary): ?array
    {
        $jdId = $request->integer('jd_document_id') ?: (int) ($documentLibrary['defaults']['jd_document_id'] ?? 0);
        $profileCvId = $request->integer('profile_cv_id');

        $cvWanted = $profileCvId > 0
            ? 'p'.$profileCvId
            : (string) ($documentLibrary['defaults']['cv_document_id'] ?? '');

        if ($jdId <= 0 || $cvWanted === '') {
            return null;
        }

        $cvTitle = null;
        foreach ($documentLibrary['cvs'] ?? [] as $cv) {
            if ((string) ($cv['id'] ?? '') === $cvWanted) {
                $cvTitle = (string) ($cv['title'] ?? 'CV');
                break;
            }
        }

        $jdTitle = null;
        foreach ($documentLibrary['jds'] ?? [] as $jd) {
            if ((int) ($jd['id'] ?? 0) === $jdId) {
                $jdTitle = (string) ($jd['title'] ?? 'Vaga');
                break;
            }
        }

        if ($cvTitle === null && $jdTitle === null) {
            return null;
        }

        return [
            'cv_title' => $cvTitle ?? 'CV',
            'jd_title' => $jdTitle ?? 'Vaga',
            'jd_id' => $jdId,
            'profile_cv_id' => $profileCvId > 0 ? $profileCvId : (int) preg_replace('/\D/', '', $cvWanted),
        ];
    }
}
