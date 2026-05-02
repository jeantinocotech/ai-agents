<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\CareerTrailStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::all();

        return view('admin.agents.index', compact('agents'));
    }

    public function create()
    {
        $trailSteps = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'cv')
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'title', 'sort_order']);

        return view('admin.agents.create', compact('trailSteps'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'organization' => 'nullable|string',
            'project_id' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'youtube_video_id' => 'nullable|string|max:150',
            'api_key' => 'nullable|string',
            'assistant_id' => 'nullable|string',
            'model_type' => 'required|string',
            'integration' => ['required', 'string', Rule::in([Agent::INTEGRATION_OPENAI, Agent::INTEGRATION_CHATKIT_WORKFLOW])],
            'chatkit_workflow_id' => 'nullable|string|max:255',
            'chatkit_workflow_version' => 'nullable|string|max:32',
            'career_trail_step_slug' => [
                'nullable',
                'string',
                Rule::exists('career_trail_steps', 'slug')->where(fn ($q) => $q->where('is_active', true)),
            ],
        ]);

        if (($validated['integration'] ?? '') === Agent::INTEGRATION_CHATKIT_WORKFLOW) {
            $request->validate([
                'chatkit_workflow_id' => 'required|string|max:255',
            ]);
        }

        $imagePath = $request->file('image')->store('agents/images', 'public');

        $chatkitWorkflowId = $validated['integration'] === Agent::INTEGRATION_CHATKIT_WORKFLOW
            ? ($validated['chatkit_workflow_id'] ?? null)
            : null;
        $chatkitWorkflowVersion = $validated['integration'] === Agent::INTEGRATION_CHATKIT_WORKFLOW
            ? ($validated['chatkit_workflow_version'] ?? '1')
            : null;

        $agent = Agent::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'youtube_video_id' => $validated['youtube_video_id'],
            'image_path' => $imagePath,
            'organization' => $validated['organization'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'system_prompt' => $validated['system_prompt'] ?? null,
            'price' => $validated['price'] ?? 1.99,
            'api_key' => $validated['api_key'],
            'assistant_id' => $validated['assistant_id'],
            'model_type' => $validated['model_type'],
            'integration' => $validated['integration'],
            'chatkit_workflow_id' => $chatkitWorkflowId,
            'chatkit_workflow_version' => $chatkitWorkflowVersion,
        ]);

        $this->syncCareerTrailStepBinding($agent, $validated['career_trail_step_slug'] ?? null);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agente criado com sucesso!');
    }

    public function edit(Agent $agent)
    {
        $steps = $agent->steps()->orderBy('step_order')->get();

        $trailSteps = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'cv')
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'title', 'sort_order']);

        $boundTrailStepSlug = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('agent_id', $agent->id)
            ->value('slug');

        return view('admin.agents.edit', compact('agent', 'steps', 'trailSteps', 'boundTrailStepSlug'));
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'organization' => 'nullable|string',
            'project_id' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'price_formatted' => 'nullable|numeric|min:0', // Use o campo hidden formatado
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'youtube_video_id' => 'nullable|string|max:150',
            'api_key' => 'nullable|string',
            'assistant_id' => 'nullable|string',
            'model_type' => 'required|string',
            'is_active' => 'nullable|boolean',
            'integration' => ['required', 'string', Rule::in([Agent::INTEGRATION_OPENAI, Agent::INTEGRATION_CHATKIT_WORKFLOW])],
            'chatkit_workflow_id' => 'nullable|string|max:255',
            'chatkit_workflow_version' => 'nullable|string|max:32',
            'career_trail_step_slug' => [
                'nullable',
                'string',
                Rule::exists('career_trail_steps', 'slug')->where(fn ($q) => $q->where('is_active', true)),
            ],
        ]);

        if (($validated['integration'] ?? '') === Agent::INTEGRATION_CHATKIT_WORKFLOW) {
            $request->validate([
                'chatkit_workflow_id' => 'required|string|max:255',
            ]);
        }

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'organization' => $validated['organization'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'system_prompt' => $validated['system_prompt'] ?? null,
            'price' => $validated['price_formatted'] ?? 1.99, // Já vem no formato correto
            'youtube_video_id' => $validated['youtube_video_id'],
            'api_key' => $validated['api_key'],
            'assistant_id' => $validated['assistant_id'],
            'model_type' => $validated['model_type'],
            'is_active' => $request->input('is_active', 0),
            'integration' => $validated['integration'],
            'chatkit_workflow_id' => $validated['chatkit_workflow_id'] ?? null,
            'chatkit_workflow_version' => $validated['chatkit_workflow_version'] ?? '1',
        ];

        if ($request->hasFile('image')) {
            // Remover imagem antiga
            if ($agent->image_path) {
                Storage::disk('public')->delete($agent->image_path);
            }
            $data['image_path'] = $request->file('image')->store('agents/images', 'public');

            Log::info('Recebendo nova imagem do agente'.$data['image_path']);

        }

        if ($data['integration'] === Agent::INTEGRATION_OPENAI) {
            $data['chatkit_workflow_id'] = null;
            $data['chatkit_workflow_version'] = null;
        } elseif ($data['integration'] === Agent::INTEGRATION_CHATKIT_WORKFLOW) {
            $data['chatkit_workflow_version'] = trim((string) ($data['chatkit_workflow_version'] ?? '')) ?: '1';
        }

        $agent->update($data);

        $this->syncCareerTrailStepBinding($agent, $validated['career_trail_step_slug'] ?? null);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agente atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $agent = Agent::findOrFail($id);

        // Verifica se tem compras
        if ($agent->purchases()->exists()) {
            return redirect()->route('admin.agents.index')
                ->with('error', 'Este agente já foi utilizado e não pode ser excluído. Você pode apenas desativá-lo.');
        }

        // Remover arquivos
        if ($agent->image_path) {
            Storage::disk('public')->delete($agent->image_path);
        }

        $agent->delete();

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agente removido com sucesso!');
    }

    public function disable($id)
    {
        $agent = Agent::findOrFail($id);
        $agent->update(['is_active' => false]);

        return back()->with('success', 'Agente desativado.');
    }

    private function syncCareerTrailStepBinding(Agent $agent, ?string $slug): void
    {
        // Um agente pode estar associado no máximo a um passo.
        CareerTrailStep::query()
            ->where('agent_id', $agent->id)
            ->update(['agent_id' => null]);

        if ($slug === null || trim($slug) === '') {
            return;
        }

        $step = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first();

        if (! $step) {
            return;
        }

        // Um passo deve ter no máximo um agente (última escolha vence).
        CareerTrailStep::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->update(['agent_id' => $agent->id]);
    }
}
