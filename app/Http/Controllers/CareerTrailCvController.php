<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\AgentDocumentDefault;
use App\Models\UserCv;
use App\Support\AgentDocumentLimits;
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
        $defaultCv = UserCv::defaultForUserId((int) $user->id);
        $agents = Agent::query()->orderBy('name')->get(['id', 'name']);

        $cvAssistantChatUrl = null;
        $assistantAgentId = config('career_trail.cv_chatkit_agent_id');
        if ($assistantAgentId) {
            $assistant = Agent::query()
                ->whereKey((int) $assistantAgentId)
                ->where('is_active', true)
                ->first();
            if ($assistant && $assistant->isChatKitWorkflow()) {
                $cvAssistantChatUrl = route('agents.chat', $assistant).'?embedded=1&no_documents=1';
            }
        }

        return view('career-trail.cv', [
            'defaultCv' => $defaultCv,
            'maxCvBodyChars' => AgentDocumentLimits::maxCharsForType(AgentDocument::TYPE_CV),
            'agents' => $agents,
            'linkedinUrl' => $user->linkedin_url,
            'cvAssistantChatUrl' => $cvAssistantChatUrl,
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
        ]);

        $hasExisting = $validated['has_existing_cv'] === '1';

        $bodyFromFile = '';
        if ($request->hasFile('cv_file')) {
            $bodyFromFile = UserCvTextExtractor::extract($request->file('cv_file'));
        }

        $bodyFromInput = isset($validated['body']) ? trim((string) $validated['body']) : '';
        $body = $bodyFromFile !== '' ? $bodyFromFile : $bodyFromInput;

        if ($body === '' || str_starts_with($body, '[Erro ao ler') || str_starts_with($body, '[Formato não suportado')) {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors(['body' => $hasExisting
                    ? 'Envie um ficheiro suportado (TXT, PDF, DOC/DOCX) ou cole o texto do CV.'
                    : 'Cole ou escreva o conteúdo do seu CV.', ]);
        }

        if (! $hasExisting && mb_strlen($body) < 40) {
            return redirect()
                ->route('career-trail.cv')
                ->withInput()
                ->withErrors(['body' => 'Para começar sem ficheiro, escreva ou cole pelo menos um parágrafo (mínimo 40 caracteres).']);
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

        UserCv::query()->where('user_id', $user->id)->update(['is_default' => false]);

        UserCv::query()->create([
            'user_id' => $user->id,
            'title' => $validated['title'] ?? null,
            'body' => $body,
            'is_default' => true,
            'source' => $request->hasFile('cv_file') ? UserCv::SOURCE_UPLOAD : UserCv::SOURCE_MANUAL,
        ]);

        return redirect()
            ->route('career-trail.cv')
            ->with('status', 'O seu CV de perfil foi guardado e definido como padrão.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $cv = UserCv::defaultForUserId((int) $user->id);

        if (! $cv) {
            return redirect()
                ->route('career-trail.cv')
                ->with('error', 'Não tem um CV de perfil para remover.');
        }

        $cv->delete();

        return redirect()
            ->route('career-trail.cv')
            ->with('status', 'O CV de perfil foi removido. Pode criar um novo quando quiser.');
    }

    public function syncToAgent(Request $request, Agent $agent): RedirectResponse
    {
        $user = $request->user();
        $cv = UserCv::defaultForUserId((int) $user->id);

        if (! $cv) {
            return redirect()
                ->route('career-trail.cv')
                ->with('error', 'Guarde primeiro um CV de perfil.');
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
            ->with('status', 'CV do perfil copiado para a biblioteca deste agente e definido como predefinido.');
    }
}
