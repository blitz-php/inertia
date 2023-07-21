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

interface Gateway
{
    /**
     * Envoyez la page Inertia au moteur de rendu côté serveur.
     */
    public function dispatch(array $page): ?Response;
}
