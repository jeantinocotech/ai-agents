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
    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('message', 'Você precisa estar logado para finalizar a compra.');
        }

        $cart = session()->get('cart', []);
        $total = array_sum(array_column($cart, 'price'));
        
        log::info('Total: ' . $total);
        log::info('cart ',  [$cart]);

        return view('cart.checkout', compact('cart', 'total'));
    }

    // Processa o checkout
    public function processCheckout(Request $request)
    {
        $cart = session()->get('cart', []);
        $user = auth()->user();

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Seu carrinho está vazio.');
        }

        foreach ($cart as $item) {
            Purchase::firstOrCreate([
                'user_id' => $user->id,
                'agent_id' => $item['id'],
            ]);
        }

        session()->forget('cart');
        return redirect()->route('checkout.success');
    }

    // Página de sucesso após checkout
    public function checkoutSuccess()
    {
        return view('cart.success');
    }
}
