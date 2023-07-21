<?php

/**
 * This file is part of Blitz PHP framework - Inertia Adapter.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Inertia\Ssr;

class Response
{
    /**
     * Préparez la réponse Inertia Server Side Rendering (SSR).
     */
    public function __construct(public string $head, public string $body)
    {
    }
}
