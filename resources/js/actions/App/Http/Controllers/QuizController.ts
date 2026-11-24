import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\QuizController::show
* @see app/Http/Controllers/QuizController.php:16
* @route '/q/{quiz}'
*/
export const show = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/q/{quiz}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\QuizController::show
* @see app/Http/Controllers/QuizController.php:16
* @route '/q/{quiz}'
*/
show.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { quiz: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'ulid' in args) {
        args = { quiz: args.ulid }
    }

    if (Array.isArray(args)) {
        args = {
            quiz: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        quiz: typeof args.quiz === 'object'
        ? args.quiz.ulid
        : args.quiz,
    }

    return show.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\QuizController::show
* @see app/Http/Controllers/QuizController.php:16
* @route '/q/{quiz}'
*/
show.get = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\QuizController::show
* @see app/Http/Controllers/QuizController.php:16
* @route '/q/{quiz}'
*/
show.head = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\QuizController::vote
* @see app/Http/Controllers/QuizController.php:108
* @route '/q/{quiz}/vote'
*/
export const vote = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: vote.url(args, options),
    method: 'post',
})

vote.definition = {
    methods: ["post"],
    url: '/q/{quiz}/vote',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\QuizController::vote
* @see app/Http/Controllers/QuizController.php:108
* @route '/q/{quiz}/vote'
*/
vote.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { quiz: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'ulid' in args) {
        args = { quiz: args.ulid }
    }

    if (Array.isArray(args)) {
        args = {
            quiz: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        quiz: typeof args.quiz === 'object'
        ? args.quiz.ulid
        : args.quiz,
    }

    return vote.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\QuizController::vote
* @see app/Http/Controllers/QuizController.php:108
* @route '/q/{quiz}/vote'
*/
vote.post = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: vote.url(args, options),
    method: 'post',
})

const QuizController = { show, vote }

export default QuizController