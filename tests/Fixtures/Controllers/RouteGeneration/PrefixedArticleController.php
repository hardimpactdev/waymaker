<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use Illuminate\Routing\Controller;
use Inertia\Response;
use HardImpact\Waymaker\Get;

class PrefixedArticleController extends Controller
{
    protected static string $routePrefix = 'articles';

    #[Get(parameters: ['article:slug'])]
    public function show(): Response
    {
        return inertia('Article/Show');
    }

    public function store(): Response
    {
        return inertia('Article/Store');
    }
}
