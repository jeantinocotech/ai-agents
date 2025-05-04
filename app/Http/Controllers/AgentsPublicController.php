<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;

class AgentsPublicController extends Controller
{
    public function index()
    {
        $agents = Agent::all();
        return view('agents.index', compact('agents'));
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
