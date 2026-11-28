import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../wayfinder'
/**
* @see \App\Http\Controllers\AuthController::logout
* @see app/Http/Controllers/AuthController.php:72
* @route '/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuthController::logout
* @see app/Http/Controllers/AuthController.php:72
* @route '/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuthController::logout
* @see app/Http/Controllers/AuthController.php:72
* @route '/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ExploreController::explore
* @see app/Http/Controllers/ExploreController.php:12
* @route '/'
*/
export const explore = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: explore.url(options),
    method: 'get',
})

explore.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExploreController::explore
* @see app/Http/Controllers/ExploreController.php:12
* @route '/'
*/
explore.url = (options?: RouteQueryOptions) => {
    return explore.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExploreController::explore
* @see app/Http/Controllers/ExploreController.php:12
* @route '/'
*/
explore.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: explore.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExploreController::explore
* @see app/Http/Controllers/ExploreController.php:12
* @route '/'
*/
explore.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: explore.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PlaysController::plays
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
export const plays = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: plays.url(options),
    method: 'get',
})

plays.definition = {
    methods: ["get","head"],
    url: '/plays',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlaysController::plays
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
plays.url = (options?: RouteQueryOptions) => {
    return plays.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlaysController::plays
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
plays.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: plays.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PlaysController::plays
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
plays.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: plays.url(options),
    method: 'head',
})
