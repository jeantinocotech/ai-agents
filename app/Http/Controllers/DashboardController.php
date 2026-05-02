<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    /**
     * Sem CV de perfil guardado, o ponto de entrada é a página inicial (orientação da Graça).
     * Com pelo menos um CV, segue-se directamente para a trilha.
     */
    public function index(): RedirectResponse
    {
        $user = auth()->user();
        if ($user !== null && ! $user->userCvs()->exists()) {
            return redirect()->route('home');
        }

        return redirect()->route('career-trail.index');
    }
}
