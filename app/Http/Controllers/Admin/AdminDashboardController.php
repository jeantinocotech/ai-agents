<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Http\Controllers\Controller;


class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalAgents = Agent::count();
        return view('admin.dashboard', compact('totalAgents'));
    }
}
