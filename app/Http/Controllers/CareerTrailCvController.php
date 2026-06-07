<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCv;
use App\Services\CareerTrailAgentAccess;
use App\Services\GamificationService;
use App\Services\ProfileCvAgentLibrary;
use App\Services\ProfileDefaultCvAtsSync;
use App\Services\UserCvDuplicateService;
use App\Support\AgentDocumentLimits;
use App\Support\AgentsDocumentTrailListFilter;
use App\Support\CareerTrailStepCompletion;
use App\Support\GoogleAnalytics;
use App\Support\UserCvTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CareerTrailCvController extends Controller
{
    public function __construct(
        private GamificationService $gamification
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        $profileCvs = UserCv::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $defaultCv = UserCv::defaultForUserId((int) $user->id);

        $editingCv = null;
        $editId = $request->query('edit');
        if ($editId !== null && $editId !== '') {
            $candidate = UserCv::query()
                ->where('user_id', $user->id)
                ->whereKey((int) $editId)
                ->first();
            $editingCv = $candidate;
        }

        $accessibleAgentIds = Agent::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id'])
            ->filter(fn (Agent $agent) => CareerTrailAgentAccess::userCanAccessTrailAgent($user, $agent))
            ->map(fn (Agent $agent): int => (int) $agent->id)
            ->values()
            ->all();

        $cvAssistantChatUrl = CareerTrailStep::cvEmbeddedCreatorChatUrl();
        $cvAssistantChatIframeUrl = $cvAssistantChatUrl !== null
            ? CareerTrailStep::cvEmbeddedCreatorChatUrl(forIframe: true)
            : null;

        $cvAnalyzeChatUrl = ($editingCv !== null && $cvAssistantChatUrl !== null)
            ? CareerTrailStep::cvAnalyzeChatUrlForUserCv((int) $editingCv->id)
            : null;

        $cvTrailStep = CareerTrailStep::query()
            ->where('slug', 'cv')
            ->where('is_active', true)
            ->first();

        $cvStepReadiness = $cvTrailStep !== null
            ? CareerTrailStepCompletion::readiness($user, $cvTrailStep)
            : ['ready' => false, 'reason' => null, 'blocked_message' => null];

        $cvStepChecklist = $cvTrailStep !== null
            ? CareerTrailStepCompletion::checklist($user, $cvTrailStep)
            : [];

        $editingCvAssociatedJds = collect();
        if ($editingCv !== null && $accessibleAgentIds !== []) {
            $editingCvAssociatedJds = AgentDocument::query()
                ->where('user_id', $user->id)
                ->where('type', AgentDocument::TYPE_JD)
                ->where('user_cv_id', $editingCv->id)
                ->whereIn('agent_id', $accessibleAgentIds)
                ->with(['agent:id,name'])
                ->orderByDesc('updated_at')
                ->get();
        }

        return view('career-trail.cv', [
            'profileCvs' => $profileCvs,
            'defaultCv' => $defaultCv,
            'editingCv' => $editingCv,
            'editingCvAssociatedJds' => $editingCvAssociatedJds,
            'maxCvBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'minProfileCvChars' => max(1, (int) config('career_trail.min_profile_cv_chars', 400)),
            'linkedinUrl' => $user->linkedin_url,
            'cvAssistantChatUrl' => $cvAssistantChatUrl,
            'cvAssistantChatIframeUrl' => $cvAssistantChatIframeUrl,
            'cvAnalyzeChatUrl' => $cvAnalyzeChatUrl,
            'cvStepReadiness' => $cvStepReadiness,
            'cvStepChecklist' => $cvStepChecklist,
            'cvTrailStep' => $cvTrailStep,
        ]);
    }

    public function extractFile(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file' => ['required', 'file', 'mimes:txt,pdf,doc,docx', 'max:20480'],
        ]);

        $file = $request->file('cv_file');
        $extracted = UserCvTextExtractor::extract($file);
        $body = trim($extracted);

        if ($body === '') {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $cvFileMsg = ($ext === 'docx' && ! UserCvTextExtractor::phpZipAvailableForDocx())
                ? 'Arquivos DOCX exigem a extensão PHP "zip" no servidor (pacote php-zip ou equivalente). Peça ao administrador para instalar ou ativar. Até lá, use PDF, TXT ou cole o texto do CV.'
                : 'Não foi possível extrair texto deste arquivo (Word/PDF). Experimente PDF ou TXT, ou cole o conteúdo na caixa de texto.';

            return response()->json([
                'message' => $cvFileMsg,
                'errors' => ['cv_file' => [$cvFileMsg]],
            ], 422);
        }

        try {
            AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, $body);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'O texto extraído excede o limite permitido para um CV.',
                'errors' => $e->errors(),
            ], 422);
        }

        $payload = ['body' => $body];
        $suggested = $this->suggestedCvTitleFromUploadedFile($file);
        if ($suggested !== null && $suggested !== '') {
            $payload['suggested_title'] = $suggested;
        }

        return response()->json($payload);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'linkedin_url' => 'nullable|string|max:512',
            'make_default' => 'sometimes|boolean',
            'export_format' => 'nullable|string|in:pdf,docx',
        ]);

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors(['title' => 'Informe um título para o CV.']);
        }

        $body = trim((string) ($validated['body'] ?? ''));

        if ($body === '') {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors(['body' => 'Digite ou cole o texto do CV na área de texto ou selecione um arquivo para extrair o texto automaticamente para a caixa antes de salvar.']);
        }

        $minChars = max(1, (int) config('career_trail.min_profile_cv_chars', 400));

        if (mb_strlen($body) < $minChars) {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors(['body' => 'O texto do CV deve ter pelo menos '.$minChars.' caracteres. Revise o conteúdo na área de texto.']);
        }

        try {
            AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, $body);
        } catch (ValidationException $e) {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors($e->errors());
        }

        $linkedin = isset($validated['linkedin_url']) ? trim((string) $validated['linkedin_url']) : '';
        $user->linkedin_url = $linkedin !== '' ? $linkedin : null;
        $user->save();

        $existingCount = UserCv::query()->where('user_id', $user->id)->count();
        $makeDefault = $existingCount === 0 ? true : $request->boolean('make_default');

        $new = UserCv::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'is_default' => false,
            'source' => UserCv::SOURCE_MANUAL,
        ]);

        $this->gamification->recordEvent(
            $user,
            'cv_created',
            UserCv::class,
            (int) $new->id
        );
        $this->gamification->ensureFreshSnapshot($user);

        if ($makeDefault) {
            $this->setUserCvAsOnlyDefault($user, $new);
        }

        if ($exportRedirect = $this->exportRedirectAfterSave($request, $new)) {
            return $exportRedirect;
        }

        $msg = $makeDefault
            ? 'Novo CV salvo e definido como padrão na conta.'
            : 'Novo CV salvo na conta.';

        GoogleAnalytics::flash('cv_saved', ['source' => UserCv::SOURCE_MANUAL]);

        return redirect()
            ->to(route('career-trail.cv', ['edit' => $new->id]).'#sec-cv-form')
            ->with('status', $msg)
            ->with('show_analisar', true);
    }

    public function update(Request $request, UserCv $userCv): RedirectResponse
    {
        $user = $request->user();
        $this->abortUnlessOwnCv($user, $userCv);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'linkedin_url' => 'nullable|string|max:512',
            'make_default' => 'sometimes|boolean',
            'export_format' => 'nullable|string|in:pdf,docx',
        ]);

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            return redirect()
                ->route('career-trail.cv', ['edit' => $userCv->id])
                ->withInput()
                ->withErrors(['title' => 'Informe um título para o CV.']);
        }

        $body = trim((string) ($validated['body'] ?? ''));

        if ($body === '') {
            return redirect()
                ->route('career-trail.cv', ['edit' => $userCv->id])
                ->withInput()
                ->withErrors(['body' => 'Digite ou cole o texto do CV na área de texto ou selecione um arquivo para extrair o texto automaticamente para a caixa antes de salvar.']);
        }

        $minChars = max(1, (int) config('career_trail.min_profile_cv_chars', 400));

        if (mb_strlen($body) < $minChars) {
            return redirect()
                ->route('career-trail.cv', ['edit' => $userCv->id])
                ->withInput()
                ->withErrors(['body' => 'O texto do CV deve ter pelo menos '.$minChars.' caracteres. Revise o conteúdo na área de texto.']);
        }

        try {
            AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, $body);
        } catch (ValidationException $e) {
            return redirect()
                ->route('career-trail.cv', ['edit' => $userCv->id])
                ->withInput()
                ->withErrors($e->errors());
        }

        $linkedin = isset($validated['linkedin_url']) ? trim((string) $validated['linkedin_url']) : '';
        $user->linkedin_url = $linkedin !== '' ? $linkedin : null;
        $user->save();

        $userCv->title = $title;
        $userCv->body = $body;
        $userCv->save();

        if ($request->boolean('make_default')) {
            $this->setUserCvAsOnlyDefault($user, $userCv);
        } elseif ($userCv->is_default) {
            ProfileDefaultCvAtsSync::sync($user);
        }

        $userCv->refresh();

        if ($exportRedirect = $this->exportRedirectAfterSave($request, $userCv)) {
            return $exportRedirect;
        }

        return redirect()
            ->to(route('career-trail.cv', ['edit' => $userCv->id]).'#sec-cv-form')
            ->with('status', 'CV atualizado.')
            ->with('show_analisar', true);
    }

    public function setDefault(Request $request, UserCv $userCv): RedirectResponse
    {
        $user = $request->user();
        $this->abortUnlessOwnCv($user, $userCv);

        $this->setUserCvAsOnlyDefault($user, $userCv);

        return redirect()
            ->route('career-trail.cv')
            ->with('status', 'CV padrão da conta atualizado.');
    }

    public function destroyProfileCv(Request $request, UserCv $userCv): RedirectResponse
    {
        $user = $request->user();
        $this->abortUnlessOwnCv($user, $userCv);

        $wasDefault = $userCv->is_default;
        $userId = (int) $user->id;
        $userCv->delete();

        if ($wasDefault) {
            $next = UserCv::query()
                ->where('user_id', $userId)
                ->orderByDesc('updated_at')
                ->first();
            if ($next !== null) {
                UserCv::query()->where('user_id', $userId)->update(['is_default' => false]);
                $next->forceFill(['is_default' => true])->save();
                ProfileDefaultCvAtsSync::sync($user);
            }
        }

        return redirect()
            ->route('career-trail.cv')
            ->with('status', 'CV removido da conta.');
    }

    public function importFromAgentDocument(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'agent_document_id' => 'required|integer|exists:agent_documents,id',
            'make_default' => 'sometimes|boolean',
        ]);

        $doc = AgentDocument::query()->findOrFail((int) $validated['agent_document_id']);

        abort_unless($doc->type === AgentDocument::TYPE_CV, 422);
        abort_unless((int) $doc->user_id === (int) $user->id, 403);

        $agent = Agent::query()->findOrFail((int) $doc->agent_id);
        CareerTrailAgentAccess::abortUnlessCanAccess($user, $agent);

        try {
            AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, (string) $doc->body);
        } catch (ValidationException $e) {
            return redirect()
                ->route('career-trail.cv')
                ->withErrors($e->errors());
        }

        $existingCount = UserCv::query()->where('user_id', $user->id)->count();
        $makeDefault = $existingCount === 0 ? true : $request->boolean('make_default');

        $new = UserCv::query()->create([
            'user_id' => $user->id,
            'title' => $doc->title ?: ('CV — '.$agent->name),
            'body' => $doc->body,
            'is_default' => false,
            'source' => UserCv::SOURCE_AGENT_IMPORT,
        ]);

        if ($makeDefault) {
            $this->setUserCvAsOnlyDefault($user, $new);
        }

        $msg = $makeDefault
            ? 'CV da biblioteca do agente copiado para a sua conta e definido como padrão.'
            : 'CV da biblioteca do agente copiado para a sua conta.';

        return redirect()
            ->route('career-trail.cv')
            ->with('status', $msg);
    }

    public function destroyAgentDocument(Request $request, Agent $agent, AgentDocument $document): RedirectResponse
    {
        $user = $request->user();
        CareerTrailAgentAccess::abortUnlessCanAccess($user, $agent);

        abort_unless(
            (int) $document->user_id === (int) $user->id
            && (int) $document->agent_id === (int) $agent->id,
            403
        );
        abort_unless($document->type === AgentDocument::TYPE_CV, 422);

        $defaults = AgentDocumentDefault::query()
            ->where('user_id', $user->id)
            ->where('agent_id', $agent->id)
            ->first();

        if ($defaults) {
            if ((int) $defaults->default_cv_document_id === (int) $document->id) {
                $defaults->default_cv_document_id = null;
            }
            if ((int) $defaults->default_jd_document_id === (int) $document->id) {
                $defaults->default_jd_document_id = null;
            }
            $defaults->save();
        }

        $document->delete();

        return redirect()
            ->route('career-trail.cv')
            ->with('status', 'CV removido da biblioteca deste agente.');
    }

    public function syncToAgent(Request $request, Agent $agent): RedirectResponse
    {
        $user = $request->user();

        try {
            $doc = ProfileCvAgentLibrary::upsertDefaultProfileCv($user, $agent, true);
        } catch (ValidationException $e) {
            return redirect()
                ->route('career-trail.cv')
                ->withErrors($e->errors());
        }

        if (! $doc) {
            return redirect()
                ->route('career-trail.cv')
                ->with('error', 'Salve primeiro um CV de perfil como padrão.');
        }

        return redirect()
            ->route('agents.documents.index', $agent)
            ->with('status', 'CV padrão copiado para a biblioteca deste agente.');
    }

    public function duplicateProfileCv(Request $request, UserCv $userCv, UserCvDuplicateService $duplicator): RedirectResponse
    {
        $user = $request->user();
        $this->abortUnlessOwnCv($user, $userCv);

        $copy = $duplicator->duplicate(
            $user,
            $userCv,
            $duplicator->titleForGenericCopy((string) $userCv->title)
        );

        return redirect()
            ->to(route('career-trail.cv', ['edit' => $copy->id]).'#sec-cv-form')
            ->with('status', 'CV duplicado — edite o texto e salve quando estiver pronto.');
    }

    public function duplicateProfileCvForAts(Request $request, UserCvDuplicateService $duplicator): RedirectResponse
    {
        $validated = $request->validate([
            'source_user_cv_id' => 'required|integer|exists:user_cvs,id',
            'job_title' => 'nullable|string|max:255',
            'edit_jd' => 'nullable|integer',
            'jd_list_filter' => 'nullable|string',
        ]);

        $user = $request->user();
        $source = UserCv::query()
            ->whereKey($validated['source_user_cv_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $jobTitle = trim((string) ($validated['job_title'] ?? ''));
        $copy = $duplicator->duplicate(
            $user,
            $source,
            $duplicator->titleForJobCopy((string) $source->title, $jobTitle !== '' ? $jobTitle : null)
        );

        $editJdId = (int) ($validated['edit_jd'] ?? 0);
        if ($editJdId > 0) {
            $jd = AgentDocument::query()
                ->whereKey($editJdId)
                ->where('user_id', $user->id)
                ->where('type', AgentDocument::TYPE_JD)
                ->firstOrFail();

            $jd->user_cv_id = $copy->id;
            $jd->save();
        }

        $query = [];
        if ($editJdId > 0) {
            $query['edit_jd'] = $editJdId;
        }
        $filter = (string) ($validated['jd_list_filter'] ?? '');
        if ($filter !== '' && $filter !== AgentsDocumentTrailListFilter::OPEN) {
            $query['jd_list_filter'] = $filter;
        }

        return redirect()
            ->to(route('career-trail.ats', $query).'#sec-ats-jd-form')
            ->with('status', 'CV duplicado para esta vaga — a cópia está seleccionada; o CV original não foi alterado.')
            ->with('ats_prefill_user_cv_id', $copy->id);
    }

    private function abortUnlessOwnCv(User $user, UserCv $userCv): void
    {
        abort_unless((int) $userCv->user_id === (int) $user->id, 403);
    }

    private function setUserCvAsOnlyDefault(User $user, UserCv $cv): void
    {
        UserCv::query()->where('user_id', $user->id)->update(['is_default' => false]);
        $cv->forceFill(['is_default' => true])->save();
        ProfileDefaultCvAtsSync::sync($user);
    }

    private function exportRedirectAfterSave(Request $request, UserCv $userCv): ?RedirectResponse
    {
        $format = $request->input('export_format');
        if (! in_array($format, ['pdf', 'docx'], true)) {
            return null;
        }

        return redirect()->route('career-trail.cv.export', [
            'userCv' => $userCv,
            'format' => $format,
        ]);
    }

    private function suggestedCvTitleFromUploadedFile(UploadedFile $file): ?string
    {
        $name = $file->getClientOriginalName();
        if ($name === '') {
            return null;
        }

        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = str_replace(['_', '-'], ' ', (string) $base);
        $collapsed = preg_replace('/\s+/u', ' ', $base);
        $trimmed = trim((string) $collapsed);
        if ($trimmed === '') {
            return null;
        }

        return mb_strlen($trimmed) > 255 ? mb_substr($trimmed, 0, 255) : $trimmed;
    }
}
