<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::all();
        return view('admin.agents.index', compact('agents'));
    }

    public function create()
    {
        return view('admin.agents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'organization' => 'required|string',
            'project_id' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'youtube_video_id' => 'nullable|string|max:150',
            'api_key' => 'nullable|string',
            'model_type' => 'required|string',
        ]);

        $imagePath = $request->file('image')->store('agents/images', 'public');

        Agent::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'youtube_video_id' => $validated['youtube_video_id'],
            'image_path' => $imagePath,
            'organization' => $validated['organization'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'system_prompt' => $validated['system_prompt'] ?? null,
            'price' => $validated['price'] ?? 1.99,
            'api_key' => $validated['api_key'],
            'model_type' => $validated['model_type'],
        ]);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agente criado com sucesso!');
    }


    public function edit(Agent $agent)
    {
        $steps = $agent->steps()->orderBy('step_order')->get();

        return view('admin.agents.edit', compact('agent', 'steps'));
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'organization' => 'required|string',
            'project_id' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'youtube_video_id' => 'nullable|string|max:150',
            'api_key' => 'nullable|string',
            'model_type' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'organization' => $validated['organization'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'system_prompt' => $validated['system_prompt'] ?? null,
            'price' => $validated['price'] ?? 1.99,
            'youtube_video_id' => $validated['youtube_video_id'],
            'api_key' => $validated['api_key'],
            'model_type' => $validated['model_type'],
            'is_active' => $request->has('is_active'),
        ];

        if ($request->hasFile('image')) {
            // Remover imagem antiga
            if ($agent->image_path) {
                Storage::disk('public')->delete($agent->image_path);
            }
            $data['image_path'] = $request->file('image')->store('agents/images', 'public');

            Log::info('Recebendo nova imagem do agente'. $data['image_path'] );

        }

        $agent->update($data);

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
}