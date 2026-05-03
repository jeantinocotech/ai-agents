<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareerTrailStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerTrailStepAdminController extends Controller
{
    public function index(): View
    {
        $steps = CareerTrailStep::query()
            ->orderBy('sort_order')
            ->get();

        return view('admin.career-trail-steps.index', compact('steps'));
    }

    public function edit(CareerTrailStep $step): View
    {
        return view('admin.career-trail-steps.edit', ['step' => $step]);
    }

    public function update(Request $request, CareerTrailStep $step): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:65000'],
            'graca_guidance' => ['nullable', 'string', 'max:65000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $step->fill([
            'title' => $validated['title'],
            'short_description' => $validated['short_description'] ?? null,
            'graca_guidance' => $validated['graca_guidance'] ?? null,
            'is_active' => $validated['is_active'],
        ]);
        $step->save();

        return redirect()
            ->route('admin.career-trail-steps.index')
            ->with('success', 'Passo «'.$step->slug.'» atualizado.');
    }
}
