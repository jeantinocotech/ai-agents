<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Chat;
use App\Models\ChatMessage;


class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $agents = Purchase::where('user_id', $user->id)
        ->with('agent')
        ->get()
        ->pluck('agent'); // Retorna só os agentes

    return view('dashboard', ['agents' => $agents]);
    }
}
