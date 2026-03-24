<?php

namespace HardImpact\Waymaker\Tests\Fixtures\Controllers\RouteGeneration;

use Illuminate\Routing\Controller;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return inertia('Contact');
    }

    public function store(): Response
    {
        return inertia('Contact/Store');
    }
}
