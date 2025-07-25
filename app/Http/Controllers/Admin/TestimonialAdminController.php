<?php

namespace App\Http\Controllers\Admin;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TestimonialAdminController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::latest()->get();
        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function approve(Testimonial $testimonial)
    {
        $testimonial->update(['is_approved' => true]);
        return back()->with('success', 'Depoimento aprovado!');
    }

    public function reject(Testimonial $testimonial)
    {
        $testimonial->update(['is_approved' => false]);
        return back()->with('success', 'Depoimento rejeitado.');
    }

    public function feature(Testimonial $testimonial)
    {
        // Define como destaque e remove destaque dos outros, se quiser só um.
        // Se quiser vários em destaque, apenas toggle
        $testimonial->update(['is_featured' => !$testimonial->is_featured]);
        return back()->with('success', 'Depoimento atualizado na home.');
    }
}
