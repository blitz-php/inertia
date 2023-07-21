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
use BlitzPHP\Http\ServerRequest;
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
        if (file_exists($manifest = './mix-manifest.json')) {
            return md5_file($manifest);
        }

        if (file_exists($manifest = './build/manifest.json')) {
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
    public function resolveValidationErrors(ServerRequestInterface $request): object
    {
        Services::session();

        // $errors = Services::validation()->getErrors();

        $errors = null;
        if (! $errors) {
            return (object) [];
        }

        if ($request->hasHeader('x-inertia-error-bag')) {
            return (object) [$request->getHeaderLine('x-inertia-error-bag') => $errors];
        }

        return (object) $errors;
    }

    /**
     * Execution middleware
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Inertia::version(fn () => $this->version($request));
        Inertia::share($this->share($request));
        Inertia::setRootView($this->rootView($request));

        if (! $request->hasHeader('X-Inertia')) {
            // return Services::response();
        }

        $this->setupDetectors($request);

        $response = $handler->handle($request);

        if ($request->getMethod() === 'GET'
            && $request->getHeaderLine('X-Inertia-Version') !== Inertia::getVersion()
        ) {
            // $response = $this->onVersionChange($request, $response);
        }

        /* if ($response->getStatusCode() === 200 &&
            empty($response->sendBody())
        ) {
            $response = $this->onEmptyResponse($request, $response);
        }
         */

        if (
            $response->getStatusCode() === StatusCode::FOUND
            && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)
        ) {
            $response = $response->withStatus(StatusCode::SEE_OTHER);
        }

        return $response
            ->withHeader('Vary', 'Accept')
            ->withHeader('X-Inertia', 'true');
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
