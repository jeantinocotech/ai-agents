<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentStep;
use Illuminate\Http\Request;

class AgentStepController extends Controller
{
    public function index(Agent $agent)
    {
        $steps = $agent->steps;
        return view('admin.agents.index', compact('agent', 'steps'));
    }

    public function create(Agent $agent)
    {
        return view('agent_steps.create', compact('agent'));
    }

    public function store(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'step_order' => 'required|integer',
            'name' => 'required|string|max:255',
            'required_input' => 'nullable|string|max:255',
            'expected_keywords' => 'nullable|string',
            'system_message' => 'nullable|string',
            'can_continue' => 'nullable|boolean',
        ]);

        $validated['expected_keywords'] = $validated['expected_keywords'] ? explode(',', $validated['expected_keywords']) : [];

        $agent->steps()->create($validated);

        return redirect()->route('admin.agents.edit', $agent)->with('success', 'Passo criado com sucesso.');
    }

    public function edit(Agent $agent, AgentStep $step)
    {
        return view('agent_steps.edit', compact('agent', 'step'));
    }

    public function update(Request $request, Agent $agent, AgentStep $step)
    {
        $validated = $request->validate([
            'step_order' => 'required|integer',
            'name' => 'required|string|max:255',
            'required_input' => 'nullable|string|max:255',
            'expected_keywords' => 'nullable|string',
            'system_message' => 'nullable|string',
            'can_continue' => 'nullable|boolean',
        ]);

        $validated['expected_keywords'] = $validated['expected_keywords'] ? explode(',', $validated['expected_keywords']) : [];

        $step->update($validated);

        return redirect()->route('admin.agents.edit', $agent)->with('success', 'Passo atualizado com sucesso.');
    }

    public function destroy(Agent $agent, AgentStep $step)
    {
        $step->delete();
        return redirect()->route('admin.agents.edit', $agent)->with('success', 'Passo deletado com sucesso.');
    }
}
