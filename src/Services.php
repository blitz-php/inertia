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

use BlitzPHP\Container\Services as BaseServices;
use BlitzPHP\Inertia\Ssr\HttpGateway;

class Services extends BaseServices
{
    public static function inertia($shared = true)
    {
        if ($shared) {
            return static::discoverServices(Factory::class, [$shared]);
        }

        return new Factory();
    }

    public static function httpGateway(bool $shared = true): HttpGateway
    {
        if ($shared) {
            return static::discoverServices(HttpGateway::class, [$shared]);
        }

        return new HttpGateway();
    }
}
