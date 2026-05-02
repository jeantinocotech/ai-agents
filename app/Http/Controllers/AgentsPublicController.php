<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgentsPublicController extends Controller
{
    /**
     * URL legada `/agentes`: utilizadores autenticados vão para a trilha;
     * visitantes são enviados para a página inicial (teaser da trilha).
     */
    public function index(): RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('career-trail.index');
        }

        return redirect()->route('home')->withFragment('trilha-teaser');
    }

    public function addToCart(Request $request, $id): RedirectResponse
    {
        Agent::findOrFail($id);

        return redirect()
            ->route(auth()->check() ? 'career-trail.index' : 'login')
            ->with('info', 'Siga a trilha para usar os assistentes com o seu saldo de tokens. Compre tokens ou faça login.');
    }
}
