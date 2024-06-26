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

use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Http\ResponsableInterface;
use BlitzPHP\Contracts\Support\Arrayable;
use BlitzPHP\Formatter\Formatter;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response as HttpResponse;
use BlitzPHP\Utilities\Iterable\Arr;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Response implements ResponsableInterface
{
    protected array $viewData = [];

    public function __construct(protected string $component, protected $props, protected string $rootView = 'app', protected $version = null)
    {
        $this->props = $props instanceof Arrayable ? $props->toArray() : $props;
    }

    public function with(array|string $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->props = array_merge($this->props, $key);
        } else {
            $this->props[$key] = $value;
        }

        return $this;
    }

    public function withViewData(array|string $key, $value = null): self
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    public function rootView(string $rootView): self
    {
        $this->rootView = $rootView;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toResponse(ServerRequestInterface $request): ResponseInterface
    {
        return $this->makeResponse($request);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response();
    }

    public function __toString(): string
    {
        return $this->makeResponse($this->request())->getBody()->getContents();
    }

    private function makeResponse(ServerRequestInterface $request): HttpResponse
    {
        $partialData = $request->getHeaderLine('X-Inertia-Partial-Data');
        $only        = array_filter(
            explode(',', $partialData ?? '')
        );

        $partialComponent = $request->getHeaderLine('X-Inertia-Partial-Component');
        $props            = ($only && ($partialComponent ?: '') === $this->component)
            ? Helpers::arrayOnly($this->props, $only)
            : $this->props;
        $props = $this->resolvePropertyInstances($props, $request);

        return $this->make([
            'component' => $this->component,
            'props'     => $props,
            'url'       => current_url(),
            'version'   => $this->version,
        ]);
    }

    /**
     * Résolvez toutes les instances de classe nécessaires dans les props donnés.
     */
    private function resolvePropertyInstances(array $props, ServerRequestInterface $request, bool $unpackDotProps = true): array
    {
        foreach ($props as $key => $value) {
            if ($value instanceof Closure) {
                $value = Helpers::closureCall($value);
            }

            if (class_exists(PromiseInterface::class) && $value instanceof PromiseInterface) {
                $value = $value->wait();
            }

            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (is_array($value)) {
                $value = $this->resolvePropertyInstances($value, $request, false);
            }

            if ($unpackDotProps && str_contains($key, '.')) {
                Arr::set($props, $key, $value);
                unset($props[$key]);
            } else {
                $props[$key] = $value;
            }
        }

        return $props;
    }

    private function make(array $page): HttpResponse
    {
        if ($this->request()->getHeaderLine('X-Inertia')) {
            $page = Formatter::type('json')->format($page);

            return $this->response()
                ->withHeader('Vary', 'X-Inertia')
                ->withHeader('X-Inertia', 'true')
                ->withHeader('Content-Type', 'application/json')
                ->withBody(to_stream($page));
        }

        return $this->response()->withStringBody($this->view($page));
    }

    private function view($page): string
    {
        return Services::viewer()
            ->setData($this->viewData + ['page' => $page], 'raw')
            ->display($this->rootView)
            ->get();
    }

    private function request(): Request
    {
        return Services::request();
    }

    private function response(): HttpResponse
    {
        return Services::response();
    }
}
