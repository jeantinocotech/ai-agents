<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\CareerTrailStep;
use App\Models\User;
use App\Models\UserCv;
use App\Services\CareerTrailAgentAccess;
use App\Support\AgentDocumentLimits;
use App\Support\CareerTrailStepCompletion;
use App\Support\UserCvTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CareerTrailCvController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $profileCvs = UserCv::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
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

        $agents = Agent::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (Agent $agent) => CareerTrailAgentAccess::userCanAccessTrailAgent($user, $agent))
            ->values();

        $accessibleAgentIds = $agents->pluck('id')->all();

        $agentLibraryCvs = collect();
        if ($accessibleAgentIds !== []) {
            $agentLibraryCvs = AgentDocument::query()
                ->where('user_id', $user->id)
                ->where('type', AgentDocument::TYPE_CV)
                ->whereIn('agent_id', $accessibleAgentIds)
                ->with(['agent:id,name'])
                ->orderByDesc('updated_at')
                ->get();
        }

        $cvAssistantChatUrl = CareerTrailStep::cvEmbeddedCreatorChatUrl();
        $cvAssistantChatIframeUrl = $cvAssistantChatUrl !== null
            ? CareerTrailStep::cvEmbeddedCreatorChatUrl(forIframe: true)
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

        return view('career-trail.cv', [
            'profileCvs' => $profileCvs,
            'defaultCv' => $defaultCv,
            'editingCv' => $editingCv,
            'agentLibraryCvs' => $agentLibraryCvs,
            'maxCvBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'minProfileCvChars' => max(1, (int) config('career_trail.min_profile_cv_chars', 40)),
            'agents' => $agents,
            'linkedinUrl' => $user->linkedin_url,
            'cvAssistantChatUrl' => $cvAssistantChatUrl,
            'cvAssistantChatIframeUrl' => $cvAssistantChatIframeUrl,
            'cvStepReadiness' => $cvStepReadiness,
            'cvStepChecklist' => $cvStepChecklist,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'has_existing_cv' => 'required|in:0,1',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'cv_file' => 'nullable|file|mimes:txt,pdf,doc,docx|max:20480',
            'linkedin_url' => 'nullable|string|max:512',
            'make_default' => 'sometimes|boolean',
        ]);

        $hasExisting = $validated['has_existing_cv'] === '1';

        $bodyFromInput = isset($validated['body']) ? trim((string) $validated['body']) : '';

        $bodyFromFile = '';
        if ($request->hasFile('cv_file')) {
            $bodyFromFile = UserCvTextExtractor::extract($request->file('cv_file'));
        }

        $body = $bodyFromFile !== '' ? $bodyFromFile : $bodyFromInput;

        if ($request->hasFile('cv_file') && $bodyFromFile === '' && $bodyFromInput === '') {
            $ext = strtolower((string) $request->file('cv_file')->getClientOriginalExtension());
            $cvFileMsg = ($ext === 'docx' && ! UserCvTextExtractor::phpZipAvailableForDocx())
                ? 'Ficheiros DOCX exigem a extensão PHP «zip» no servidor (pacote php-zip ou equivalente). Peça ao administrador para a instalar ou activar. Até lá, use PDF, TXT ou cole o texto do CV.'
                : 'Não foi possível extrair texto deste ficheiro (Word/PDF). Experimente PDF ou TXT, ou cole o conteúdo na caixa de texto.';

            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors([
                    'cv_file' => $cvFileMsg,
                    'body' => 'Se o Word tiver só tabelas ou caixas de texto complexas, cole aqui o texto ou exporte para PDF.',
                ]);
        }

        if ($body === '' || str_starts_with($body, '[Erro ao ler') || str_starts_with($body, '[Formato não suportado')) {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors(['body' => $hasExisting
                    ? 'Envie um ficheiro suportado (TXT, PDF, DOC/DOCX) ou cole o texto do CV.'
                    : 'Cole ou escreva o conteúdo do seu CV.', ]);
        }

        $minChars = max(1, (int) config('career_trail.min_profile_cv_chars', 40));

        if (! $hasExisting && mb_strlen($body) < $minChars) {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors(['body' => 'Para começar sem ficheiro, escreva ou cole pelo menos um parágrafo (mínimo '.$minChars.' caracteres).']);
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
            'title' => $validated['title'] ?? null,
            'body' => $body,
            'is_default' => false,
            'source' => ($request->hasFile('cv_file') && $bodyFromFile !== '') ? UserCv::SOURCE_UPLOAD : UserCv::SOURCE_MANUAL,
        ]);

        if ($makeDefault) {
            $this->setUserCvAsOnlyDefault($user, $new);
        }

        $msg = $makeDefault
            ? 'Novo CV guardado e definido como predefinido na conta.'
            : 'Novo CV guardado na conta.';

        return redirect()
            ->route('career-trail.cv')
            ->with('status', $msg);
    }

    public function update(Request $request, UserCv $userCv): RedirectResponse
    {
        $user = $request->user();
        $this->abortUnlessOwnCv($user, $userCv);

        $validated = $request->validate([
            'has_existing_cv' => 'required|in:0,1',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'cv_file' => 'nullable|file|mimes:txt,pdf,doc,docx|max:20480',
            'linkedin_url' => 'nullable|string|max:512',
            'make_default' => 'sometimes|boolean',
        ]);

        $hasExisting = $validated['has_existing_cv'] === '1';

        $bodyFromInput = isset($validated['body']) ? trim((string) $validated['body']) : '';

        $bodyFromFile = '';
        if ($request->hasFile('cv_file')) {
            $bodyFromFile = UserCvTextExtractor::extract($request->file('cv_file'));
        }

        $body = $bodyFromFile !== '' ? $bodyFromFile : $bodyFromInput;

        if ($request->hasFile('cv_file') && $bodyFromFile === '' && $bodyFromInput === '') {
            $ext = strtolower((string) $request->file('cv_file')->getClientOriginalExtension());
            $cvFileMsg = ($ext === 'docx' && ! UserCvTextExtractor::phpZipAvailableForDocx())
                ? 'Ficheiros DOCX exigem a extensão PHP «zip» no servidor (pacote php-zip ou equivalente). Peça ao administrador para a instalar ou activar. Até lá, use PDF, TXT ou cole o texto do CV.'
                : 'Não foi possível extrair texto deste ficheiro (Word/PDF). Experimente PDF ou TXT, ou cole o conteúdo na caixa de texto.';

            return redirect()
                ->route('career-trail.cv', ['edit' => $userCv->id])
                ->withInput()
                ->withErrors([
                    'cv_file' => $cvFileMsg,
                    'body' => 'Se o Word tiver só tabelas ou caixas de texto complexas, cole aqui o texto ou exporte para PDF.',
                ]);
        }

        if ($body === '' || str_starts_with($body, '[Erro ao ler') || str_starts_with($body, '[Formato não suportado')) {
            return redirect()
                ->route('career-trail.cv', ['edit' => $userCv->id])
                ->withInput()
                ->withErrors(['body' => $hasExisting
                    ? 'Envie um ficheiro suportado (TXT, PDF, DOC/DOCX) ou cole o texto do CV.'
                    : 'Cole ou escreva o conteúdo do seu CV.', ]);
        }

        $minChars = max(1, (int) config('career_trail.min_profile_cv_chars', 40));

        if (! $hasExisting && mb_strlen($body) < $minChars) {
            return redirect()
                ->route('career-trail.cv', ['edit' => $userCv->id])
                ->withInput()
                ->withErrors(['body' => 'Para começar sem ficheiro, escreva ou cole pelo menos um parágrafo (mínimo '.$minChars.' caracteres).']);
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

        $userCv->title = $validated['title'] ?? null;
        $userCv->body = $body;
        if ($request->hasFile('cv_file') && $bodyFromFile !== '') {
            $userCv->source = UserCv::SOURCE_UPLOAD;
        }
        $userCv->save();

        if ($request->boolean('make_default')) {
            $this->setUserCvAsOnlyDefault($user, $userCv);
        }

        return redirect()
            ->route('career-trail.cv')
            ->with('status', 'CV atualizado.');
    }

    public function setDefault(Request $request, UserCv $userCv): RedirectResponse
    {
        $user = $request->user();
        $this->abortUnlessOwnCv($user, $userCv);

        $this->setUserCvAsOnlyDefault($user, $userCv);

        return redirect()
            ->route('career-trail.cv')
            ->with('status', 'CV predefinido da conta atualizado.');
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
            ? 'CV da biblioteca do agente copiado para a sua conta e definido como predefinido.'
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
        CareerTrailAgentAccess::abortUnlessCanAccess($user, $agent);

        $cv = UserCv::defaultForUserId((int) $user->id);

        if (! $cv) {
            return redirect()
                ->route('career-trail.cv')
                ->with('error', 'Guarde primeiro um CV de perfil predefinido.');
        }

        try {
            AgentDocumentLimits::assertBodyWithinLimit(AgentDocument::TYPE_CV, $cv->body);
        } catch (ValidationException $e) {
            return redirect()
                ->route('career-trail.cv')
                ->withErrors($e->errors());
        }

        $doc = AgentDocument::query()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'type' => AgentDocument::TYPE_CV,
            'title' => $cv->title ?: 'CV do perfil',
            'body' => $cv->body,
            'paired_cv_document_id' => null,
        ]);

        $defaults = AgentDocumentDefault::firstOrNew([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
        ]);
        $defaults->default_cv_document_id = $doc->id;
        $defaults->save();

        return redirect()
            ->route('agents.documents.index', $agent)
            ->with('status', 'CV predefinido copiado para a biblioteca deste agente e definido como predefinido.');
    }

    private function abortUnlessOwnCv(User $user, UserCv $userCv): void
    {
        abort_unless((int) $userCv->user_id === (int) $user->id, 403);
    }

    private function setUserCvAsOnlyDefault(User $user, UserCv $cv): void
    {
        UserCv::query()->where('user_id', $user->id)->update(['is_default' => false]);
        $cv->forceFill(['is_default' => true])->save();
    }
}
