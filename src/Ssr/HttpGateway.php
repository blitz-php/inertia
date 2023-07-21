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

use BlitzPHP\Config\Config;
use BlitzPHP\Inertia\Helpers;
use BlitzPHP\Inertia\Services;
use Exception;

class HttpGateway implements Gateway
{
    /**
     * @var array Configuration a utiliser pour inertia
     */
    protected array $config;

    /**
     * @var array Configuration par defaut de Inertia
     */
    private array $defaultConfig = [
        'ssr' => [
            'enabled' => false,
            'url'     => 'http://127.0.0.1:13714/render',
        ],
    ];

    public function __construct()
    {
        $config = Config::get('inertia');
        if (empty($config)) {
            $config = $this->defaultConfig;
        }

        $this->config = (array) $config;
    }

    /**
     * Envoyez la page Inertia au moteur de rendu côté serveur.
     */
    public function dispatch(array $page): ?Response
    {
        if (! $this->getConfig('ssr.enabled', false)) {
            return null;
        }

        $url = $this->getConfig('ssr.url', 'http://127.0.0.1:13714/render');

        try {
            $client   = Services::httpclient();
            $response = $client->asJson()->acceptJson()->post($url, $page)->json();
        } catch (Exception $e) {
            return null;
        }

        if (null === $response) {
            return null;
        }

        return new Response(
            implode("\n", $response['head']),
            $response['body']
        );
    }

    /**
     * Obtenez la configuration de la garde.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getConfig(string $name, $default = null)
    {
        return Helpers::arrayGet($this->config, $name, $default);
    }
}
