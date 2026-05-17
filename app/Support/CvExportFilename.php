<?php

namespace App\Support;

use App\Models\UserCv;
use Illuminate\Support\Str;

final class CvExportFilename
{
    public static function build(UserCv $userCv, string $format): string
    {
        $slug = Str::slug(trim((string) $userCv->title), '-');
        if ($slug === '') {
            $slug = 'cv-'.$userCv->id;
        }

        $slug = Str::limit($slug, 80, '');

        return $slug.'.'.strtolower($format);
    }
}
