<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Testimonial;
use Auth;

class TestimonialController extends Controller
{
    public function create()
    {
        // Se quiser passar agents para select, envie eles aqui
        $agents = Agent::where('is_active', true)->get();

        $userPhoto = Auth::user()->profile_photo ? asset('storage/' . Auth::user()->profile_photo) : null;

        // Busca o último depoimento do usuário autenticado
        $lastTestimonial = \App\Models\Testimonial::where('user_id', Auth::id())
        ->latest()
        ->first();

        $selected_avatar = $lastTestimonial?->author_image;

        // Busca todos os arquivos de avatar no diretório
        $avatars = [];
        $avatarPath = public_path('img/avatars');
        if (is_dir($avatarPath)) {
            $files = scandir($avatarPath);
            foreach ($files as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['svg', 'png', 'jpg', 'jpeg'])) {
                    $avatars[] = 'img/avatars/' . $file;
                }
            }
        }

        return view('testimonials.create', compact('agents', 'selected_avatar', 'avatars', 'userPhoto'));

    }

    public function store(Request $request)
    {
        $request->validate([
            'author_name' => 'required|string|max:60',
            'author_role' => 'nullable|string|max:60',
            'content' => 'required|string|max:500',
            'agent_id' => 'nullable|exists:agents,id',
            'image_option' => 'required|in:profile,avatar',
            'author_avatar' => 'nullable|string', // só valida se avatar
        ]);

        // Decide a imagem do autor:
        if ($request->image_option === 'avatar' && $request->author_avatar) {
            $authorImage = asset('avatars/' . $request->author_avatar);
        } else {
            // Usa foto de perfil do usuário
            $authorImage = Auth::user()->profile_photo_url ?? asset('img/default-avatar.png');
        }

        Testimonial::create([
            'user_id' => Auth::id(),
            'author_name' => $request->author_name,
            'author_role' => $request->author_role,
            'content' => $request->content,
            'agent_id' => $request->agent_id,
            'author_image' => $authorImage,
            'is_approved' => false,
            'is_featured' => false,
        ]);
    

        return redirect()->route('testimonials.create')
        ->with('success', 'Depoimento enviado! Após aprovação, ele poderá aparecer no site.');
    }

        // Controller
        public function mine()
        {
            $testimonials = \App\Models\Testimonial::where('user_id', Auth::id())->latest()->get();
            return view('testimonials.mine', compact('testimonials'));
        }
}
