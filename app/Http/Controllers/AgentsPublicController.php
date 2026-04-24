<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentsPublicController extends Controller
{
    public function index()
    {

        Log::info('AgentsPublicController@index called');

        $user = auth()->user();
        $user?->refresh();

        $testimonials = Testimonial::where('is_approved', true)
            ->where('is_featured', true)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        $agents = Agent::withAvg('ratings', 'rating')
            ->where('is_active', true)
            ->get();

        return view('agents.index', compact('agents', 'testimonials', 'user'));

    }

    public function addToCart(Request $request, $id)
    {
        Agent::findOrFail($id);

        return redirect()
            ->route(auth()->check() ? 'tokens.purchase' : 'login')
            ->with('info', 'Use tokens para conversar com os agentes. Compre um pacote ou faça login.');
    }
}
