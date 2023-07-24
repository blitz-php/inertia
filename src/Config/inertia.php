<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | Ces options configurent si et comment Inertia utilise le rendu côté serveur pour pré-rendre les visites initiales effectuées sur les pages de votre application.
    |
    | Notez que l'activation de ces options ne fera PAS automatiquement fonctionner SSR, car un service de rendu distinct doit être disponible.
    | Pour en savoir plus, veuillez visiter https://inertiajs.com/server-side-rendering
    |
    */
    'ssr' => [
        'enabled' => false,
        'url'     => 'http://127.0.0.1:13714/render',
    ],
];
