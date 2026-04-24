<?php

namespace App\Http\Controllers;

use App\Models\Agent;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $user->refresh();

        $agents = Agent::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'agents' => $agents,
            'tokenBalance' => (int) $user->token_balance,
        ]);
    }
}
