<?php

use App\Rules\ValidProfilePhoto;
use Illuminate\Http\UploadedFile;

test('valid profile photo accepts png with image x-png mime', function () {
    $path = sys_get_temp_dir().'/profile-test-'.uniqid().'.png';
    file_put_contents($path, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));

    $file = new UploadedFile($path, 'screenshot.png', 'image/x-png', null, true);
    $rule = new ValidProfilePhoto;
    $failed = false;
    $rule->validate('profile_photo', $file, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();

    @unlink($path);
});
