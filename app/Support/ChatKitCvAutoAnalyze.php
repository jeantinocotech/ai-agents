<?php

namespace App\Support;

use Illuminate\Http\Request;

final class ChatKitCvAutoAnalyze
{
    /**
     * Valor do select ChatKit (ex. p12) quando a página abre com ?user_cv_id=&auto_send=1.
     */
    public static function preselectCvIdFromRequest(Request $request, ?array $documentLibrary): ?string
    {
        if (! is_array($documentLibrary) || $request->query('auto_send') !== '1') {
            return null;
        }

        $userCvId = $request->integer('user_cv_id');
        if ($userCvId <= 0) {
            return null;
        }

        $wanted = 'p'.$userCvId;
        foreach ($documentLibrary['cvs'] ?? [] as $cv) {
            if ((string) ($cv['id'] ?? '') === $wanted) {
                return $wanted;
            }
        }

        return null;
    }
}
