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
            'author_image' => 'required',
            'author_image_upload' => 'nullable|image|max:2048',
        ]);

        $authorImage = null;

        // Decide a imagem do autor:
        if ($request->author_image == 'profile_photo') {
            $authorImage = Auth::user()->profile_photo ? 'storage/' . Auth::user()->profile_photo : null;
        } elseif ($request->author_image == 'upload' && $request->hasFile('author_image_upload')) {
            $authorImage = $request->file('author_image_upload')->store('testimonials', 'public');
            $authorImage = 'storage/' . $authorImage;
        } else {
            // Assume que foi selecionado um avatar
            $authorImage = $request->author_image;
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
