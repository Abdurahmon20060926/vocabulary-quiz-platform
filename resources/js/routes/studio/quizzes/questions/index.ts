import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StudioController::store
* @see app/Http/Controllers/StudioController.php:304
* @route '/studio/quizzes/{quiz}/questions'
*/
export const store = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/studio/quizzes/{quiz}/questions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudioController::store
* @see app/Http/Controllers/StudioController.php:304
* @route '/studio/quizzes/{quiz}/questions'
*/
store.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::store
* @see app/Http/Controllers/StudioController.php:304
* @route '/studio/quizzes/{quiz}/questions'
*/
store.post = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudioController::update
* @see app/Http/Controllers/StudioController.php:327
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
export const update = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/studio/quizzes/{quiz}/questions/{questionId}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\StudioController::update
* @see app/Http/Controllers/StudioController.php:327
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
update.url = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            quiz: args[0],
            questionId: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        quiz: typeof args.quiz === 'object'
        ? args.quiz.ulid
        : args.quiz,
        questionId: args.questionId,
    }

    return update.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace('{questionId}', parsedArgs.questionId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::update
* @see app/Http/Controllers/StudioController.php:327
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
update.put = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\StudioController::destroy
* @see app/Http/Controllers/StudioController.php:350
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
export const destroy = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/studio/quizzes/{quiz}/questions/{questionId}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\StudioController::destroy
* @see app/Http/Controllers/StudioController.php:350
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
destroy.url = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            quiz: args[0],
            questionId: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        quiz: typeof args.quiz === 'object'
        ? args.quiz.ulid
        : args.quiz,
        questionId: args.questionId,
    }

    return destroy.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace('{questionId}', parsedArgs.questionId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::destroy
* @see app/Http/Controllers/StudioController.php:350
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
destroy.delete = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const questions = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default questions