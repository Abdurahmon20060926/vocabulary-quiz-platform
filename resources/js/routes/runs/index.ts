import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\RunController::start
* @see app/Http/Controllers/RunController.php:15
* @route '/q/{quiz}/runs'
*/
export const start = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/q/{quiz}/runs',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RunController::start
* @see app/Http/Controllers/RunController.php:15
* @route '/q/{quiz}/runs'
*/
start.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return start.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RunController::start
* @see app/Http/Controllers/RunController.php:15
* @route '/q/{quiz}/runs'
*/
start.post = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RunController::answer
* @see app/Http/Controllers/RunController.php:55
* @route '/runs/{run}/answers'
*/
export const answer = (args: { run: string | number } | [run: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: answer.url(args, options),
    method: 'post',
})

answer.definition = {
    methods: ["post"],
    url: '/runs/{run}/answers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RunController::answer
* @see app/Http/Controllers/RunController.php:55
* @route '/runs/{run}/answers'
*/
answer.url = (args: { run: string | number } | [run: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { run: args }
    }

    if (Array.isArray(args)) {
        args = {
            run: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        run: args.run,
    }

    return answer.definition.url
            .replace('{run}', parsedArgs.run.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RunController::answer
* @see app/Http/Controllers/RunController.php:55
* @route '/runs/{run}/answers'
*/
answer.post = (args: { run: string | number } | [run: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: answer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RunController::submit
* @see app/Http/Controllers/RunController.php:83
* @route '/runs/{run}/submit'
*/
export const submit = (args: { run: string | number } | [run: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/runs/{run}/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RunController::submit
* @see app/Http/Controllers/RunController.php:83
* @route '/runs/{run}/submit'
*/
submit.url = (args: { run: string | number } | [run: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { run: args }
    }

    if (Array.isArray(args)) {
        args = {
            run: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        run: args.run,
    }

    return submit.definition.url
            .replace('{run}', parsedArgs.run.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RunController::submit
* @see app/Http/Controllers/RunController.php:83
* @route '/runs/{run}/submit'
*/
submit.post = (args: { run: string | number } | [run: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

const runs = {
    start: Object.assign(start, start),
    answer: Object.assign(answer, answer),
    submit: Object.assign(submit, submit),
}

export default runs