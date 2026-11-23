<?php

namespace App\Http\Controllers;

use App\Models\QuizUserStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaysController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = QuizUserStat::with(['quiz' => function ($q) {
                $q->with('user:id,name,avatar_url')->withCount('questions');
            }])
            ->where('user_id', $user->id)
            ->orderByDesc('last_played_at')
            ->paginate(20);

        return Inertia::render('MyPlays', [
            'stats' => $stats,
        ]);
    }
}
