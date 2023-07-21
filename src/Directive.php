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

class Directive
{
    /**
     * Compile la directive Inertia.
     */
    public static function inertia(array $page, string $id = ''): string
    {
        $id           = trim(trim($id), "\\'\"") ?: 'app';
        $__inertiaSsr = Services::httpGateway()->dispatch($page);

        if ($__inertiaSsr instanceof Response) {
            $template = $__inertiaSsr->body;
        } else {
            $template = '<div id="' . $id . '" data-page="' . htmlentities(json_encode($page)) . '"></div>';
        }

        return implode(' ', array_map('trim', explode("\n", $template)));
    }

    /**
     * Compile la directive d'entete inertia.
     */
    public static function inertiaHead(array $page): string
    {
        $__inertiaSsr = Services::httpGateway()->dispatch($page);

        if ($__inertiaSsr instanceof Response) {
            $template = $__inertiaSsr->head;
        } else {
            $template = '';
        }

        return implode(' ', array_map('trim', explode("\n", $template)));
    }
}
