<?php

use App\Models\User;
use App\Models\UserCv;

test('user can export own cv as pdf', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV Comercial 2026',
        'body' => "Experiência\n\nEmpresa X — Analista",
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.cv.export', ['userCv' => $cv, 'format' => 'pdf']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('cv-comercial-2026.pdf');
});

test('user can export own cv as docx', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'Meu Curriculum',
        'body' => 'Linha um',
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->get(route('career-trail.cv.export', ['userCv' => $cv, 'format' => 'docx']))
        ->assertOk()
        ->assertDownload('meu-curriculum.docx');
});

test('docx export escapes characters that break word xml', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV A & B',
        'body' => "Empresa X — cargo\nLinha com <tags> & aspersão",
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $response = $this->actingAs($user)
        ->get(route('career-trail.cv.export', ['userCv' => $cv, 'format' => 'docx']))
        ->assertOk()
        ->assertDownload('cv-a-b.docx');

    $file = $response->baseResponse->getFile();
    $content = file_get_contents($file->getPathname());
    expect(strlen($content))->toBeGreaterThan(100);

    $path = sys_get_temp_dir().'/cv_docx_test_'.uniqid('', true).'.docx';
    file_put_contents($path, $content);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $documentXml = $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($path);

    expect($documentXml)->toContain('&amp;')
        ->and($documentXml)->toContain('&lt;tags&gt;')
        ->not->toContain('A & B')
        ->not->toContain('<tags> &');
});

test('user cannot export another users cv', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $cv = UserCv::query()->create([
        'user_id' => $owner->id,
        'title' => 'Privado',
        'body' => str_repeat('a', 500),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($other)
        ->get(route('career-trail.cv.export', ['userCv' => $cv, 'format' => 'pdf']))
        ->assertForbidden();
});

test('cv update with export format redirects to download', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $cv = UserCv::query()->create([
        'user_id' => $user->id,
        'title' => 'CV Teste',
        'body' => str_repeat('Conteúdo do CV. ', 40),
        'is_default' => true,
        'source' => UserCv::SOURCE_MANUAL,
    ]);

    $this->actingAs($user)
        ->patch(route('career-trail.cv.update', $cv), [
            'title' => 'CV Teste',
            'body' => str_repeat('Conteúdo actualizado. ', 40),
            'export_format' => 'pdf',
        ])
        ->assertRedirect(route('career-trail.cv.export', ['userCv' => $cv, 'format' => 'pdf']));
});
