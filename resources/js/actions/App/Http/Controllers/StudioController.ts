import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StudioController::index
* @see app/Http/Controllers/StudioController.php:19
* @route '/studio'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/studio',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StudioController::index
* @see app/Http/Controllers/StudioController.php:19
* @route '/studio'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::index
* @see app/Http/Controllers/StudioController.php:19
* @route '/studio'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StudioController::index
* @see app/Http/Controllers/StudioController.php:19
* @route '/studio'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

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

/**
* @see \App\Http\Controllers\StudioController::storeQuestion
* @see app/Http/Controllers/StudioController.php:281
* @route '/studio/quizzes/{quiz}/questions'
*/
export const storeQuestion = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeQuestion.url(args, options),
    method: 'post',
})

storeQuestion.definition = {
    methods: ["post"],
    url: '/studio/quizzes/{quiz}/questions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StudioController::storeQuestion
* @see app/Http/Controllers/StudioController.php:281
* @route '/studio/quizzes/{quiz}/questions'
*/
storeQuestion.url = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions) => {
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

    return storeQuestion.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::storeQuestion
* @see app/Http/Controllers/StudioController.php:281
* @route '/studio/quizzes/{quiz}/questions'
*/
storeQuestion.post = (args: { quiz: string | number | { ulid: string | number } } | [quiz: string | number | { ulid: string | number } ] | string | number | { ulid: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeQuestion.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StudioController::updateQuestion
* @see app/Http/Controllers/StudioController.php:310
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
export const updateQuestion = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateQuestion.url(args, options),
    method: 'put',
})

updateQuestion.definition = {
    methods: ["put"],
    url: '/studio/quizzes/{quiz}/questions/{questionId}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\StudioController::updateQuestion
* @see app/Http/Controllers/StudioController.php:310
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
updateQuestion.url = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions) => {
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

    return updateQuestion.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace('{questionId}', parsedArgs.questionId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::updateQuestion
* @see app/Http/Controllers/StudioController.php:310
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
updateQuestion.put = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateQuestion.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\StudioController::destroyQuestion
* @see app/Http/Controllers/StudioController.php:339
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
export const destroyQuestion = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyQuestion.url(args, options),
    method: 'delete',
})

destroyQuestion.definition = {
    methods: ["delete"],
    url: '/studio/quizzes/{quiz}/questions/{questionId}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\StudioController::destroyQuestion
* @see app/Http/Controllers/StudioController.php:339
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
destroyQuestion.url = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions) => {
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

    return destroyQuestion.definition.url
            .replace('{quiz}', parsedArgs.quiz.toString())
            .replace('{questionId}', parsedArgs.questionId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StudioController::destroyQuestion
* @see app/Http/Controllers/StudioController.php:339
* @route '/studio/quizzes/{quiz}/questions/{questionId}'
*/
destroyQuestion.delete = (args: { quiz: string | number | { ulid: string | number }, questionId: string | number } | [quiz: string | number | { ulid: string | number }, questionId: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyQuestion.url(args, options),
    method: 'delete',
})

const StudioController = { index, create, store, edit, update, destroy, publish, unpublish, duplicate, stats, storeQuestion, updateQuestion, destroyQuestion }

export default StudioController