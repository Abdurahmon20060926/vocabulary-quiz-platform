import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import questions from './questions'
/**
* @see \App\Http\Controllers\StudioController::create
* @see app/Http/Controllers/StudioController.php:46
* @route '/studio/quizzes/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/studio/quizzes/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StudioController::create
* @see app/Http/Controllers/StudioController.php:46
* @route '/studio/quizzes/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::create
* @see app/Http/Controllers/StudioController.php:46
* @route '/studio/quizzes/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StudioController::create
* @see app/Http/Controllers/StudioController.php:46
* @route '/studio/quizzes/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StudioController::store
* @see app/Http/Controllers/StudioController.php:54
* @route '/studio/quizzes'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/studio/quizzes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudioController::store
* @see app/Http/Controllers/StudioController.php:54
* @route '/studio/quizzes'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::store
* @see app/Http/Controllers/StudioController.php:54
* @route '/studio/quizzes'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudioController::edit
* @see app/Http/Controllers/StudioController.php:68
* @route '/studio/quizzes/{quiz}/edit'
*/
export const edit = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/studio/quizzes/{quiz}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StudioController::edit
* @see app/Http/Controllers/StudioController.php:68
* @route '/studio/quizzes/{quiz}/edit'
*/
edit.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::edit
* @see app/Http/Controllers/StudioController.php:68
* @route '/studio/quizzes/{quiz}/edit'
*/
edit.get = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StudioController::edit
* @see app/Http/Controllers/StudioController.php:68
* @route '/studio/quizzes/{quiz}/edit'
*/
edit.head = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StudioController::update
* @see app/Http/Controllers/StudioController.php:93
* @route '/studio/quizzes/{quiz}'
*/
export const update = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/studio/quizzes/{quiz}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\StudioController::update
* @see app/Http/Controllers/StudioController.php:93
* @route '/studio/quizzes/{quiz}'
*/
update.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::update
* @see app/Http/Controllers/StudioController.php:93
* @route '/studio/quizzes/{quiz}'
*/
update.put = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\StudioController::destroy
* @see app/Http/Controllers/StudioController.php:116
* @route '/studio/quizzes/{quiz}'
*/
export const destroy = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/studio/quizzes/{quiz}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\StudioController::destroy
* @see app/Http/Controllers/StudioController.php:116
* @route '/studio/quizzes/{quiz}'
*/
destroy.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::destroy
* @see app/Http/Controllers/StudioController.php:116
* @route '/studio/quizzes/{quiz}'
*/
destroy.delete = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\StudioController::publish
* @see app/Http/Controllers/StudioController.php:135
* @route '/studio/quizzes/{quiz}/publish'
*/
export const publish = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publish.url(args, options),
    method: 'post',
})

publish.definition = {
    methods: ["post"],
    url: '/studio/quizzes/{quiz}/publish',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudioController::publish
* @see app/Http/Controllers/StudioController.php:135
* @route '/studio/quizzes/{quiz}/publish'
*/
publish.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return publish.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::publish
* @see app/Http/Controllers/StudioController.php:135
* @route '/studio/quizzes/{quiz}/publish'
*/
publish.post = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publish.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudioController::unpublish
* @see app/Http/Controllers/StudioController.php:152
* @route '/studio/quizzes/{quiz}/unpublish'
*/
export const unpublish = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unpublish.url(args, options),
    method: 'post',
})

unpublish.definition = {
    methods: ["post"],
    url: '/studio/quizzes/{quiz}/unpublish',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudioController::unpublish
* @see app/Http/Controllers/StudioController.php:152
* @route '/studio/quizzes/{quiz}/unpublish'
*/
unpublish.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return unpublish.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::unpublish
* @see app/Http/Controllers/StudioController.php:152
* @route '/studio/quizzes/{quiz}/unpublish'
*/
unpublish.post = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unpublish.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudioController::duplicate
* @see app/Http/Controllers/StudioController.php:165
* @route '/studio/quizzes/{quiz}/duplicate'
*/
export const duplicate = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: duplicate.url(args, options),
    method: 'post',
})

duplicate.definition = {
    methods: ["post"],
    url: '/studio/quizzes/{quiz}/duplicate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudioController::duplicate
* @see app/Http/Controllers/StudioController.php:165
* @route '/studio/quizzes/{quiz}/duplicate'
*/
duplicate.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return duplicate.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::duplicate
* @see app/Http/Controllers/StudioController.php:165
* @route '/studio/quizzes/{quiz}/duplicate'
*/
duplicate.post = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: duplicate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudioController::stats
* @see app/Http/Controllers/StudioController.php:178
* @route '/studio/quizzes/{quiz}/stats'
*/
export const stats = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(args, options),
    method: 'get',
})

stats.definition = {
    methods: ["get","head"],
    url: '/studio/quizzes/{quiz}/stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StudioController::stats
* @see app/Http/Controllers/StudioController.php:178
* @route '/studio/quizzes/{quiz}/stats'
*/
stats.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return stats.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::stats
* @see app/Http/Controllers/StudioController.php:178
* @route '/studio/quizzes/{quiz}/stats'
*/
stats.get = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StudioController::stats
* @see app/Http/Controllers/StudioController.php:178
* @route '/studio/quizzes/{quiz}/stats'
*/
stats.head = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: stats.url(args, options),
    method: 'head',
})

const quizzes = {
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    publish: Object.assign(publish, publish),
    unpublish: Object.assign(unpublish, unpublish),
    duplicate: Object.assign(duplicate, duplicate),
    stats: Object.assign(stats, stats),
    questions: Object.assign(questions, questions),
}

export default quizzes