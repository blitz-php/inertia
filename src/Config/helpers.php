<?php

use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Http\Redirection;
use BlitzPHP\Inertia\Services;

if (! function_exists('inertia')) {
    /**
     * Inertia helper.
     *
     * @return \BlitzPHP\Inertia\Factory|\BlitzPHP\Inertia\Response
     */
    function inertia(?string $component = null, array|Arrayable $props = [])
    {
        $instance = Services::inertia();

        if ($component) {
            return $instance->render($component, $props);
        }

        return $instance;
    }
}

if (! function_exists('inertia_location')) {
    /**
     * Inertia location helper.
     */
    function inertia_location(string $url): Redirection
    {
        return Services::inertia()->location($url);
    }
}
