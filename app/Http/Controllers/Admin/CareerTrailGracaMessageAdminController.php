<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareerTrailGracaMessage;
use App\Models\CareerTrailStep;
use App\Support\CareerTrailGracaSlots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CareerTrailGracaMessageAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filterStepId = $request->query('career_trail_step_id');

        $messages = CareerTrailGracaMessage::query()
            ->with('step:id,slug,title')
            ->when(
                $filterStepId !== null && $filterStepId !== '',
                fn ($q) => $q->where('career_trail_step_id', (int) $filterStepId)
            )
            ->orderBy('process_key')
            ->orderByRaw('career_trail_step_id is null')
            ->orderBy('career_trail_step_id')
            ->orderBy('slot')
            ->orderBy('sort_order')
            ->get();

        return view('admin.career-trail-graca-messages.index', compact('messages', 'filterStepId'));
    }

    public function create(): View
    {
        $steps = CareerTrailStep::query()->orderBy('sort_order')->get(['id', 'slug', 'title']);
        $slots = CareerTrailGracaSlots::forAdminSelect();

        return view('admin.career-trail-graca-messages.create', compact('steps', 'slots'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request, null);

        CareerTrailGracaMessage::query()->create($validated);

        return redirect()
            ->route('admin.career-trail-graca-messages.index')
            ->with('success', 'Mensagem criada.');
    }

    public function edit(CareerTrailGracaMessage $career_trail_graca_message): View
    {
        $steps = CareerTrailStep::query()->orderBy('sort_order')->get(['id', 'slug', 'title']);
        $slots = CareerTrailGracaSlots::forAdminSelect();
        $message = $career_trail_graca_message;

        return view('admin.career-trail-graca-messages.edit', compact('message', 'steps', 'slots'));
    }

    public function update(Request $request, CareerTrailGracaMessage $career_trail_graca_message): RedirectResponse
    {
        $validated = $this->validated($request, $career_trail_graca_message);

        $career_trail_graca_message->update($validated);

        return redirect()
            ->route('admin.career-trail-graca-messages.index')
            ->with('success', 'Mensagem actualizada.');
    }

    public function destroy(CareerTrailGracaMessage $career_trail_graca_message): RedirectResponse
    {
        $career_trail_graca_message->delete();

        return redirect()
            ->route('admin.career-trail-graca-messages.index')
            ->with('success', 'Mensagem removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CareerTrailGracaMessage $existing = null): array
    {
        $slotKeys = array_keys(CareerTrailGracaSlots::labels());

        $stepId = $request->input('career_trail_step_id');
        $stepId = ($stepId === null || $stepId === '') ? null : (int) $stepId;

        $uniqueSort = Rule::unique('career_trail_graca_messages', 'sort_order')
            ->where('process_key', (string) $request->input('process_key'))
            ->where('slot', (string) $request->input('slot'))
            ->where(function ($query) use ($stepId) {
                if ($stepId === null) {
                    $query->whereNull('career_trail_step_id');
                } else {
                    $query->where('career_trail_step_id', $stepId);
                }
            });

        if ($existing !== null) {
            $uniqueSort = $uniqueSort->ignore($existing);
        }

        $validated = $request->validate([
            'process_key' => ['required', 'string', 'max:64'],
            'career_trail_step_id' => ['nullable', 'integer', 'exists:career_trail_steps,id'],
            'slot' => ['required', 'string', 'max:64', Rule::in($slotKeys)],
            'body' => ['nullable', 'string', 'max:65000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:32767', $uniqueSort],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['career_trail_step_id'] = $stepId;

        return $validated;
    }
}
