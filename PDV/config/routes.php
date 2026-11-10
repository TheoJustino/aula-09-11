<?php

declare(strict_types=1);

return [
    'GET /' => static fn (): array => [
        'view' => 'home',
        'title' => 'MVC funcionando',
        'message' => 'A estrutura inicial da aplicação PDV está funcionando.',
    ],
];