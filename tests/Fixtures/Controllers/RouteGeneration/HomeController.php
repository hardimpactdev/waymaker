<?php

namespace HardImpact\Waymaker\Tests\Fixtures\Controllers\RouteGeneration;

use Illuminate\Routing\Controller;
use Inertia\Response;

class HomeController extends Controller
{
    public function show(): Response
    {
        return inertia('Home');
    }

    public function index(): Response
    {
        return inertia('Home/Index');
    }
}
