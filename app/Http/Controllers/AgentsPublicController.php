<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentsPublicController extends Controller
{
    public function index()
    {
        
        Log::info('AgentsPublicController@index called');
        
        $user = auth()->user();
        $purchasedAgentIds = [];
    
        if ($user) {
            $purchasedAgentIds = $user->purchases()->pluck('agent_id')->toArray(); }
    
            $agents = Agent::withAvg('ratings', 'rating')
            ->where('is_active', true)
            ->get();


        Log::info('Purchased Agent IDs: ' , [$purchasedAgentIds], [$agents]);
    
        return view('agents.index', compact('agents', 'purchasedAgentIds'));

    }
    

    public function addToCart(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        // adiciona ao carrinho na sessão
        $cart = session()->get('cart', []);
        $cart[$id] = [
            'id' => $agent->id,
            'name' => $agent->name,
            'price' => $agent->price,       
            'description' => $agent->description,
        ];
        session()->put('cart', $cart);

        return redirect()->back()->with('success', 'Agente adicionado ao carrinho!');
    }
}
