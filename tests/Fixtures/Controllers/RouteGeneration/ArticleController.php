<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use Illuminate\Routing\Controller;
use Inertia\Response;
use HardImpact\Waymaker\Delete;
use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Patch;
use HardImpact\Waymaker\Post;
use HardImpact\Waymaker\Put;

class ArticleController extends Controller
{
    protected static string $routePrefix = 'articles';

    protected static array $routeMiddleware = ['auth', 'verified'];

    #[Get(parameters: ['article:slug'])]
    public function show(string $article): Response
    {
        return inertia('Article/Show', [
            'article' => $article,
        ]);
    }

    #[Post]
    public function store(): Response
    {
        return inertia('Article/Store');
    }

    #[Put(parameters: ['article:slug'])]
    public function update(string $article): Response
    {
        return inertia('Article/Update', [
            'article' => $article,
        ]);
    }

    #[Patch(parameters: ['article:slug'])]
    public function edit(string $article): Response
    {
        return inertia('Article/Edit', [
            'article' => $article,
        ]);
    }

    #[Delete(parameters: ['article:slug'])]
    public function destroy(string $article): Response
    {
        return inertia('Article/Destroy', [
            'article' => $article,
        ]);
    }
}
