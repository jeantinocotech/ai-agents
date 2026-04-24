<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class TokenSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'tokens_welcome_amount' => '5000',
            'tokens_renewal_amount' => '1000',
            'tokens_renewal_interval_days' => '30',
            'tokens_minimum_per_request' => '1',
            'token_pack_amount' => '10000',
            'token_pack_price' => '49.90',
            'usage_billing_mode' => 'estimated_usd',
            'platform_tokens_per_usd' => '2000',
            'openai_input_price_per_million_usd' => '5',
            'openai_output_price_per_million_usd' => '15',
            'anthropic_input_price_per_million_usd' => '3',
            'anthropic_output_price_per_million_usd' => '15',
            'chatkit_tokens_per_session' => '50',
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::query()->where('key', $key)->doesntExist()) {
                Setting::set($key, $value);
            }
        }
    }
}
