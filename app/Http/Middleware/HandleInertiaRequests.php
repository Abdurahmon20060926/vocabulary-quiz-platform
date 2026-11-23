<?php

namespace App\Http\Middleware;

use App\Models\QuizUserStat;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $recentPlays = null;
        if ($user) {
            $recentPlays = QuizUserStat::with(['quiz:id,ulid,title,mode,status'])
                ->where('user_id', $user->id)
                ->orderByDesc('last_played_at')
                ->limit(5)
                ->get()
                ->map(fn($s) => [
                    'quiz_ulid' => $s->quiz->ulid ?? null,
                    'quiz_title' => $s->quiz->title ?? 'Unknown',
                    'quiz_status' => $s->quiz->status ?? 'unknown',
                    'best_reached' => $s->best_reached,
                    'passed' => $s->passed,
                    'last_played_at' => $s->last_played_at,
                ])
                ->filter(fn($p) => $p['quiz_ulid'] !== null)
                ->values();
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'recentPlays' => $recentPlays,
        ]);
    }
}
