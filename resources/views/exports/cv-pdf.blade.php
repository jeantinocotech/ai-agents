<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1e293b; line-height: 1.45; margin: 36pt; }
        h1 { font-size: 16pt; margin: 0 0 16pt; color: #0f172a; }
        .body { white-space: pre-wrap; word-wrap: break-word; }
        .footer { margin-top: 24pt; font-size: 8pt; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="body">{{ $body }}</div>
    <p class="footer">Gerado no GratoAI — {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
</body>
</html>
