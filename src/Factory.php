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

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Http\Redirection;
use Closure;
use Psr\Http\Message\RequestInterface;

class Factory
{
    /**
     * Proprietes partagees par tous les composants
     */
    protected array $sharedProps = [];

    /**
     * Page racine
     */
    protected string $rootView = 'app';

    /**
     * Version du manifest.json
     */
    protected string|Closure|null $version = null;

    /**
     * Modifie la page racine
     */
    public function setRootView(string $name): void
    {
        $this->rootView = $name;
    }

    /**
     * Definit les donnees partagees par toute l'application
     */
    public function share(array|string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            Helpers::arraySet($this->sharedProps, $key, $value);
        }
    }

    /**
     * @param null $key
     */
    public function getShared($key = null): array
    {
        $sharedProps = $this->sharedProps;

        array_walk_recursive($sharedProps, static function (&$sharedProp) {
            $sharedProp = Helpers::closureCall($sharedProp);
        });

        if ($key) {
            return Helpers::arrayGet($sharedProps, $key);
        }

        return $sharedProps;
    }

    /**
     * Modifie la version inertia
     */
    public function version(string|Closure $version): void
    {
        $this->version = $version;
    }

    /**
     * Recupere la version
     */
    public function getVersion(): string
    {
        return (string) Helpers::closureCall($this->version);
    }

    public function render($component, array|Arrayable $props = []): Response
    {
        if ($props instanceof Arrayable) {
            $props = $props->toArray();
        }

        return new Response(
            $component,
            array_merge($this->sharedProps, $props),
            $this->rootView,
            $this->getVersion()
        );
    }

    public function app(array $page): string
    {
        return Directive::inertia($page);
    }

    public function head(array $page): string
    {
        return Directive::inertiaHead($page);
    }

    /**
     * Redirection vers une URL
     */
    public function redirect(string $uri): Redirection
    {
        return $this->redirectResponse()->to($uri, StatusCode::SEE_OTHER);
    }

    /**
     * Reponse de redirection
     */
    public function redirectResponse(): Redirection
    {
        return Services::redirection(true);
    }

    /**
     * Redirection
     */
    public function location(RequestInterface|string $url): Redirection
    {
        if ($url instanceof RequestInterface) {
            $url = (string) $url->getUri();
        }

        if (Services::request()->hasHeader('X-Inertia')) {
            Services::session()->setPreviousUrl($url);

            return $this->redirectResponse()
                ->withHeader('X-Inertia-Location', $url)
                ->withStatus(StatusCode::CONFLICT);
        }

        return $this->redirect($url);
    }
}
