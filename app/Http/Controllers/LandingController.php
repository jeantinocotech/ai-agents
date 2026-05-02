<?php

namespace App\Http\Controllers;

use App\Models\CareerTrailStep;
use App\Models\Testimonial;
use App\Models\UserCv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user() !== null && UserCv::query()->where('user_id', $request->user()->id)->exists()) {
            return redirect()->route('career-trail.index');
        }

        $testimonials = Testimonial::query()
            ->where('is_approved', true)
            ->where('is_featured', true)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        $trailTeaserSteps = CareerTrailStep::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'sort_order', 'title', 'short_description']);

        $cvCreatorChatUrl = null;
        if ($request->user() !== null) {
            $cvCreatorChatUrl = CareerTrailStep::cvEmbeddedCreatorChatUrl();
        }

        return view('landing', [
            'testimonials' => $testimonials,
            'trailTeaserSteps' => $trailTeaserSteps,
            'cvCreatorChatUrl' => $cvCreatorChatUrl,
        ]);
    }
}
