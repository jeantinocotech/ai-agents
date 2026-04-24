<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const TOKEN_KEYS = [
        'tokens_welcome_amount',
        'tokens_renewal_amount',
        'tokens_renewal_interval_days',
        'tokens_minimum_per_request',
        'token_pack_amount',
        'token_pack_price',
        'usage_billing_mode',
        'platform_tokens_per_usd',
        'openai_input_price_per_million_usd',
        'openai_output_price_per_million_usd',
        'anthropic_input_price_per_million_usd',
        'anthropic_output_price_per_million_usd',
        'chatkit_tokens_per_session',
    ];

    public function editTokens(): View
    {
        $values = [];
        foreach (self::TOKEN_KEYS as $key) {
            $values[$key] = Setting::get($key, '');
        }

        return view('admin.settings.tokens', compact('values'));
    }

    public function updateTokens(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tokens_welcome_amount' => ['required', 'integer', 'min:0'],
            'tokens_renewal_amount' => ['required', 'integer', 'min:0'],
            'tokens_renewal_interval_days' => ['required', 'integer', 'min:1'],
            'tokens_minimum_per_request' => ['required', 'integer', 'min:1'],
            'token_pack_amount' => ['required', 'integer', 'min:1'],
            'token_pack_price' => ['required', 'numeric', 'min:0'],
            'usage_billing_mode' => ['required', 'in:estimated_usd,api_tokens'],
            'platform_tokens_per_usd' => ['required', 'numeric', 'min:0.000001'],
            'openai_input_price_per_million_usd' => ['required', 'numeric', 'min:0'],
            'openai_output_price_per_million_usd' => ['required', 'numeric', 'min:0'],
            'anthropic_input_price_per_million_usd' => ['required', 'numeric', 'min:0'],
            'anthropic_output_price_per_million_usd' => ['required', 'numeric', 'min:0'],
            'chatkit_tokens_per_session' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value);
        }

        return redirect()->route('admin.settings.tokens.edit')
            ->with('success', 'Parâmetros de tokens atualizados.');
    }
}
