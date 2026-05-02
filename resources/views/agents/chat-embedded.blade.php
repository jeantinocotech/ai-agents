@extends('layouts.embedded')

@section('content')
    <div class="mx-auto max-w-7xl p-3 sm:p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200/80">
            <a href="{{ route('career-trail.cv') }}" target="_parent"
               class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Voltar ao meu CV</a>
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-600">
                <span class="font-semibold text-slate-800">{{ $agent->name }}</span>
                <span class="hidden text-slate-300 sm:inline" aria-hidden="true">|</span>
                <span>Saldo <strong id="token-balance-value" data-live-token-balance class="tabular-nums text-slate-900">{{ number_format($tokenBalance ?? 0, 0, ',', '.') }}</strong></span>
                <span class="hidden text-slate-300 sm:inline" aria-hidden="true">|</span>
                <span>Nesta visita <strong id="chatkit-session-tokens-used" class="tabular-nums text-amber-700">0</strong></span>
            </div>
        </div>

        @php
            $motivationLettersIndexUrl = $motivationLettersIndexUrl ?? null;
            $interviewPreparationsIndexUrl = $interviewPreparationsIndexUrl ?? null;
            $chatkitSimpleChat = $chatkitSimpleChat ?? false;
            $ckLib = $documentLibrary ?? ['cvs' => [], 'jds' => [], 'defaults' => ['cv_document_id' => null, 'jd_document_id' => null]];
            $ckDefCv = $ckLib['defaults']['cv_document_id'] ?? null;
            $ckDefJd = $ckLib['defaults']['jd_document_id'] ?? null;
            $ckMaxCv = (int) ($ckLib['max_cv_body_chars'] ?? config('agent_documents.max_cv_body_chars', 60000));
            $ckMaxJd = (int) ($ckLib['max_jd_body_chars'] ?? config('agent_documents.max_jd_body_chars', 60000));
            $ckConsultTokens = (int) ($chatkitConsultationTokens ?? 0);
        @endphp

        @if ($chatkitSimpleChat)
            @include('agents.partials.chatkit-workspace-simple')
        @else
            @include('agents.partials.chatkit-workspace')
        @endif

        <div id="modal-container"></div>
    </div>
@endsection

@push('scripts')
    @include('agents.partials.chatkit-main-scripts')
    @include('agents.partials.chatkit-rating-scripts')
@endpush
