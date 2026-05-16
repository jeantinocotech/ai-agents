<?php

namespace App\Http\Controllers;

use App\Support\GracaPanelPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GracaPanelPreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_key' => ['required', 'string', Rule::in(GracaPanelPreferences::pageKeys())],
            'collapsed' => ['required', 'boolean'],
            'apply_global' => ['sometimes', 'nullable', 'boolean'],
            'dismiss_global_prompt' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'page_key' => $validated['page_key'],
            'collapsed' => (bool) $validated['collapsed'],
        ];

        if (array_key_exists('apply_global', $validated) && $validated['apply_global'] !== null) {
            $payload['apply_global'] = (bool) $validated['apply_global'];
        }

        if (! empty($validated['dismiss_global_prompt'])) {
            $payload['dismiss_global_prompt'] = true;
        }

        $user = $request->user();
        GracaPanelPreferences::applyUpdate($user, $payload);
        $user->refresh();

        return response()->json([
            'ok' => true,
            'graca_panel' => GracaPanelPreferences::read($user),
        ]);
    }
}
