<?php

namespace App\Http\Controllers;

use App\Models\UserCv;
use App\Services\CvExportService;
use Illuminate\Http\Request;

class UserCvExportController extends Controller
{
    public function __invoke(Request $request, UserCv $userCv, string $format, CvExportService $exporter)
    {
        abort_unless(in_array($format, ['pdf', 'docx'], true), 404);
        abort_unless($request->user() !== null && (int) $userCv->user_id === (int) $request->user()->id, 403);

        return $exporter->download($userCv, $format);
    }
}
