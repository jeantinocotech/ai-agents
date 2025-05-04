<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'video' => 'required|string|max:150',
            'api_key' => 'nullable|string',
            'model_type' => 'required|string',
        ]);

        $imagePath = $request->file('image')->store('agents/images', 'public');

        Agent::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'video_path' => $validated['video'],
            'api_key' => $validated['api_key'],
            'model_type' => $validated['model_type'],
        ]);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agente criado com sucesso!');
    }

    public function edit($id)
    {
        $agent = Agent::findOrFail($id);
        return view('admin.agents.edit', compact('agent'));
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'video' => 'required|string|max:150',
            'api_key' => 'nullable|string',
            'model_type' => 'required|string',
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'video_path' => $validated['video'],
            'api_key' => $validated['api_key'],
            'model_type' => $validated['model_type'],
        ];

        if ($request->hasFile('image')) {
            // Remover imagem antiga
            if ($agent->image_path) {
                Storage::disk('public')->delete($agent->image_path);
            }
            $data['image_path'] = $request->file('image')->store('agents/images', 'public');
        }

        $agent->update($data);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agente atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $agent = Agent::findOrFail($id);
        
        // Remover arquivos
        if ($agent->image_path) {
            Storage::disk('public')->delete($agent->image_path);
        }
        
        $agent->delete();
        
        return redirect()->route('admin.agents.index')
            ->with('success', 'Agente removido com sucesso!');
    }
}