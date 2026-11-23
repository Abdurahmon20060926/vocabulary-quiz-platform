<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->get('sort', 'popular');
        $mode = $request->get('mode', 'all');
        $search = $request->get('search', '');

        $query = Quiz::query()
            ->where('status', 'published')
            ->where('is_listed', true)
            ->with('user:id,name,avatar_url')
            ->withCount('questions');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($mode !== 'all') {
            $query->where('mode', $mode);
        }

        match ($sort) {
            'played' => $query->orderByDesc('runs_count'),
            'newest' => $query->orderByDesc('published_at'),
            default => $query->orderByDesc('upvotes_count'),
        };

        $query->orderByDesc('id');

        $quizzes = $query->paginate(18)->withQueryString();

        return Inertia::render('Explore', [
            'quizzes' => $quizzes,
            'filters' => [
                'sort' => $sort,
                'mode' => $mode,
                'search' => $search,
            ],
        ]);
    }
}
