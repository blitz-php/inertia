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
use BlitzPHP\Formatter\Formatter;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response as HttpResponse;

class Response
{
    protected array $viewData = [];

    public function __construct(protected $component, protected array $props, protected string $rootView = 'app', protected $version = null)
    {
    }

    public function with($key, $value = null): self
    {
        if (is_array($key)) {
            $this->props = array_merge($this->props, $key);
        } else {
            $this->props[$key] = $value;
        }

        return $this;
    }

    public function withViewData($key, $value = null): self
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    public function __toString()
    {
        $partialData = $this->request()->getHeaderLine('X-Inertia-Partial-Data');
        $only        = array_filter(
            explode(',', $partialData ?? '')
        );

        $partialComponent = $this->request()->getHeader('X-Inertia-Partial-Component');
        $props            = ($only && ($partialComponent ?: '') === $this->component)
            ? Helpers::arrayOnly($this->props, $only)
            : $this->props;

        array_walk_recursive($props, static function (&$prop) {
            $prop = Helpers::closureCall($prop);
        });

        return $this->make([
            'component' => $this->component,
            'props'     => $props,
            'url'       => current_url(),
            'version'   => $this->version,
        ]);
    }

    private function make(array $page): string
    {
        if ($this->request()->getHeaderLine('X-Inertia')) {
            $page = Formatter::type('json')->format($page);

            return (string) $this->response()
                ->withHeader('Vary', 'X-Inertia')
                ->withHeader('X-Inertia', 'true')
                ->withHeader('Content-Type', 'application/json')
                ->withBody(to_stream($page));
        }

        return $this->view($page);
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
