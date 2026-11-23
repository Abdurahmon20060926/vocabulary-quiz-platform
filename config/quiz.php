<?php

return [
    'grace_seconds' => (float) env('QUIZ_GRACE_SECONDS', 1.5),
    'suggestion_w_author' => (float) env('SUGGESTION_W_AUTHOR', 3),
    'suggestion_w_stats' => (float) env('SUGGESTION_W_STATS', 2),
    'suggestion_w_votes' => (float) env('SUGGESTION_W_VOTES', 1),
];
