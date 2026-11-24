import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PlaysController::index
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/plays',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlaysController::index
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlaysController::index
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PlaysController::index
* @see app/Http/Controllers/PlaysController.php:12
* @route '/plays'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const PlaysController = { index }

export default PlaysController