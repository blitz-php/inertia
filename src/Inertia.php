<?php

/**
 * This file is part of Blitz PHP framework - Inertia Adapter.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Inertia;

/**
 * @method static array                   getShared(string $key = null)
 * @method static int|string              getVersion()
 * @method static \BlitzPHP\Http\Response location($url)
 * @method static Response                render($component, array $props = [])
 * @method static void                    setRootView(string $name)
 * @method static void                    share($key, $value = null)
 * @method static void                    version($version)
 *
 * @see Factory
 */
class Inertia
{
    public static function __callStatic($method, $arguments)
    {
        return Services::inertia()->{$method}(...$arguments);
    }
}
