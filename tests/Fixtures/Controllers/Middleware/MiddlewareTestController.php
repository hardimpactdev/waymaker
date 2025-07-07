<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;
use HardImpact\Waymaker\Put;
use Illuminate\Routing\Controller;
use Inertia\Response;

class MiddlewareTestController extends Controller
{
    protected static string $routePrefix = 'middleware-test';

    protected static array $routeMiddleware = ['controller-mw'];

    #[Post(middleware: 'method-mw')]
    public function store(): Response
    {
        return inertia('Middleware/Store');
    }

    #[Get(middleware: ['method-mw', 'another-mw'])]
    public function index(): Response
    {
        return inertia('Middleware/Index');
    }

    #[Get(uri: 'no-method-mw')]
    public function noMethodMiddleware(): Response
    {
        return inertia('Middleware/NoMethod');
    }

    #[Put(middleware: ['controller-mw', 'unique-method-mw'])]
    public function update(): Response
    {
        return inertia('Middleware/Update');
    }
}
