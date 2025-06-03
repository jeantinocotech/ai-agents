<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Purchase;

class CartController extends Controller
{
    // Exibe o carrinho
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('cart.index', compact('cart'));
    }

    // Adiciona agente ao carrinho
    public function addToCart($id)
    {
        $agent = Agent::findOrFail($id);

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            return redirect()->route('cart.index')->with('info', 'Agente já está no carrinho.');
        }

        $cart[$id] = [
            'id' => $agent->id,
            'name' => $agent->name,
            'price' => $agent->price,
            'description' => $agent->description,
        ];

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Agente adicionado ao carrinho.');
    }

    // Remove agente do carrinho
    public function removeFromCart($id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Agente removido do carrinho.');
    }

    // Limpa o carrinho inteiro
    public function clearCart()
    {
        session()->forget('cart');
        return redirect()->route('cart.index')->with('success', 'Carrinho limpo com sucesso.');
    }

    // Página de checkout (precisa estar logado)
    // Página de checkout - agora direcionando para Hotmart
    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('message', 'Você precisa estar logado para finalizar a compra.');
        }

        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Seu carrinho está vazio.');
        }

        // Busca os agentes completos do banco
        $agentIds = array_keys($cart);
        $agents = Agent::whereIn('id', $agentIds)->get();
        
        // Verifica se todos os agentes têm URL da Hotmart configurada
        $agentsWithoutUrl = $agents->whereNull('hotmart_checkout_url');
        if ($agentsWithoutUrl->isNotEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Alguns agentes não têm checkout configurado.');
        }

        $total = array_sum(array_column($cart, 'price'));
        
        Log::info('Checkout iniciado', [
            'user_id' => auth()->id(),
            'agents' => $agentIds,
            'total' => $total
        ]);

        return view('cart.checkout', compact('agents', 'total'));
    }

    // Página de sucesso após checkout
     // Página de sucesso após checkout
    public function checkoutSuccess()
    {
        // Limpa o carrinho após sucesso
        session()->forget('cart');
        return view('cart.success');
    }

}
