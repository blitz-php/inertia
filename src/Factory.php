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
use BlitzPHP\Http\Redirection;
use Psr\Http\Message\RequestInterface;

class Factory
{
    /**
     * @var array proprietes partagees par tous les composants
     */
    protected array $sharedProps = [];

    /**
     * @var string page racine
     */
    protected string $rootView = 'app';

    /**
     * @var string|null version du manifest.json
     */
    protected $version;

    /**
     * Modifie la page racine
     */
    public function setRootView(string $name): void
    {
        $this->rootView = $name;
    }

    /**
     * @param null $value
     */
    public function share($key, $value = null): void
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
     *
     * @param mixed $version
     */
    public function version($version): void
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

    public function render($component, array $props = []): string
    {
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
     *
     * @param RequestInterface|string $url
     */
    public function location($url): Redirection
    {
        if ($url instanceof RequestInterface) {
            $url = $url->getUri();
        }

        if (Services::request()->hasHeader('X-Inertia')) {
            Services::session()->set('_blitz_previous_url', $url);

            return $this->redirectResponse()
                ->withHeader('X-Inertia-Location', $url)
                ->withStatus(StatusCode::CONFLICT);
        }

        return $this->redirect((string) $url);
    }
}
