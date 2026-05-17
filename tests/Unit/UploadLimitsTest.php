<?php

use App\Support\UploadLimits;

test('ini size parser converts megabytes', function () {
    expect(UploadLimits::iniSizeToBytes('2M'))->toBe(2 * 1024 * 1024);
    expect(UploadLimits::iniSizeToBytes('5M'))->toBe(5 * 1024 * 1024);
});

test('profile photo max respects php upload limit', function () {
    $max = UploadLimits::profilePhotoMaxBytes();
    $phpUpload = UploadLimits::iniSizeToBytes((string) ini_get('upload_max_filesize'));

    expect($max)->toBeLessThanOrEqual(UploadLimits::PROFILE_PHOTO_APP_MAX_BYTES);
    expect($max)->toBeLessThanOrEqual($phpUpload);
});
