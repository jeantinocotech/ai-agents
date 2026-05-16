Alerta ChatKit (relatório do browser)

Data: {{ $reportedAt->timezone(config('app.timezone'))->format('Y-m-d H:i:s T') }}
Ambiente: {{ config('app.env') }}

Utilizador: #{{ $userId }} ({{ $userEmail }})
Agente: #{{ $agentId }} — {{ $agentName }}
Origem: {{ $source }}

Mensagem:
{{ $message }}

@if ($referer !== '')
Referer:
{{ $referer }}
@endif

Consulte também storage/logs/laravel.log (procurar "ChatKit cliente").
