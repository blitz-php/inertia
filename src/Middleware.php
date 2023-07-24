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
use BlitzPHP\Http\Request;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Utilities\Iterable\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    /**
     * Le template racine qui est chargé lors de la première visite de la page.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    protected string $rootView = 'app';

    /**
     * Détermine la version actuelle des assets.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(ServerRequestInterface $request): ?string
    {
		$app = config('app');

        if (isset($app['asset_url'])) {
            return md5($app['asset_url']);
        }

        if (file_exists($manifest = public_path('mix-manifest.json'))) {
            return md5_file($manifest);
        }

        if (file_exists($manifest = public_path('build/manifest.json'))) {
            return md5_file($manifest);
        }

        return null;
    }

    /**
     * Définit les donnees partagés par défaut.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(ServerRequestInterface $request): array
    {
        return [
            'errors' => fn () => $this->resolveValidationErrors($request),
        ];
    }

    /**
     * Définit le template racine qui est chargé lors de la première visite de page.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    public function rootView(ServerRequestInterface $request): string
    {
        return $this->rootView;
    }

    /**
     * Détermine ce qu'il faut faire lorsqu'une action d'inertia est renvoyée sans réponse.
     * Par défaut, nous redirigerons l'utilisateur vers son lieu d'origine.
     */
    public function onEmptyResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Inertia::redirectResponse()->back();
    }

    /**
     * Dans le cas où les actifs changent, lancez une visite d'emplacement côté client pour forcer une mise à jour.
     */
    public function onVersionChange(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Inertia::location((string) $request->getUri());
    }

    /**
     * Résout et prépare les erreurs de validation de manière à ce qu'elles soient plus faciles à utiliser côté client.
     */
    public function resolveValidationErrors(ServerRequestInterface $request): array
    {
        if (!($request instanceof Request)) {
            return [];
        }

        if (! $request->hasSession() || ! $request->session()->has('errors')) {
            return [];
        }
        
        return collect($request->session()->get('errors'))->map(function ($errors) {
            if (!is_array($errors)) {
                $errors = ['default' => $errors];
            }
            return collect($errors)->toArray();
        })->pipe(function (Collection $errors) use ($request) {
			if ($errors->has('default') && $request->header('x-inertia-error-bag')) {
                return [$request->header('x-inertia-error-bag') => $errors->get('default')];
            }
			if ($errors->has('default')) {
                return $errors->get('default');
            }

            return $errors;
        })->toArray();
    }

    /**
     * Execution middleware
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Inertia::version(fn () => $this->version($request));
        Inertia::share($this->share($request));
        Inertia::setRootView($this->rootView($request));

        $this->setupDetectors($request);

        $response = $handler->handle($request);
        $response = $response->withHeader('Vary', 'X-Inertia');

        if (! $request->hasHeader('X-Inertia')) {
            return $response;
        }

        if ($request->getMethod() === 'GET' && $request->getHeaderLine('X-Inertia-Version') !== Inertia::getVersion()) {
            $response = $this->onVersionChange($request, $response);
        }

        if ($response->getStatusCode() === StatusCode::OK && empty($response->getBody()->getContents())) {
            $response = $this->onEmptyResponse($request, $response);
        }

        if ($response->getStatusCode() === StatusCode::FOUND && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)) {
            $response = $response->withStatus(StatusCode::SEE_OTHER);
        }

        return $response;
    }

    /**
     * Définissez des détecteurs dans la requête pour l'utiliser dans toute l'application.
     */
    private function setupDetectors(ServerRequestInterface $request): void
    {
        if (! ($request instanceof ServerRequest)) {
            return;
        }

        $request->addDetector('inertia', static fn (ServerRequestInterface $r) => $r->hasHeader('X-Inertia'));
        $request->addDetector('inertia-partial-component', static fn (ServerRequestInterface $r) => $r->hasHeader('X-Inertia-Partial-Component'));
        $request->addDetector('inertia-partial-data', static fn (ServerRequestInterface $r) => $r->hasHeader('X-Inertia-Partial-Data'));
    }
}
