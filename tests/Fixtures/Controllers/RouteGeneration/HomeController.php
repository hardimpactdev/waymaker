<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

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
