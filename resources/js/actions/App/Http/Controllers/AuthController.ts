import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AuthController::redirectToGoogle
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
export const redirectToGoogle = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirectToGoogle.url(options),
    method: 'get',
})

redirectToGoogle.definition = {
    methods: ["get","head"],
    url: '/auth/google/redirect',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuthController::redirectToGoogle
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
redirectToGoogle.url = (options?: RouteQueryOptions) => {
    return redirectToGoogle.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuthController::redirectToGoogle
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
redirectToGoogle.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirectToGoogle.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuthController::redirectToGoogle
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
redirectToGoogle.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: redirectToGoogle.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuthController::handleGoogleCallback
* @see app/Http/Controllers/AuthController.php:24
* @route '/auth/google/callback'
*/
export const handleGoogleCallback = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: handleGoogleCallback.url(options),
    method: 'get',
})

handleGoogleCallback.definition = {
    methods: ["get","head"],
    url: '/auth/google/callback',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuthController::handleGoogleCallback
* @see app/Http/Controllers/AuthController.php:24
* @route '/auth/google/callback'
*/
handleGoogleCallback.url = (options?: RouteQueryOptions) => {
    return handleGoogleCallback.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuthController::handleGoogleCallback
* @see app/Http/Controllers/AuthController.php:24
* @route '/auth/google/callback'
*/
handleGoogleCallback.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: handleGoogleCallback.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuthController::handleGoogleCallback
* @see app/Http/Controllers/AuthController.php:24
* @route '/auth/google/callback'
*/
handleGoogleCallback.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: handleGoogleCallback.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AuthController::logout
* @see app/Http/Controllers/AuthController.php:46
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
* @see app/Http/Controllers/AuthController.php:46
* @route '/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuthController::logout
* @see app/Http/Controllers/AuthController.php:46
* @route '/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

const AuthController = { redirectToGoogle, handleGoogleCallback, logout }

export default AuthController