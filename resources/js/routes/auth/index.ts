import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import google723582 from './google'
/**
* @see \App\Http\Controllers\AuthController::google
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
export const google = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: google.url(options),
    method: 'get',
})

google.definition = {
    methods: ["get","head"],
    url: '/auth/google/redirect',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuthController::google
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
google.url = (options?: RouteQueryOptions) => {
    return google.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuthController::google
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
google.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: google.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AuthController::google
* @see app/Http/Controllers/AuthController.php:13
* @route '/auth/google/redirect'
*/
google.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: google.url(options),
    method: 'head',
})

const auth = {
    google: Object.assign(google, google723582),
}

export default auth