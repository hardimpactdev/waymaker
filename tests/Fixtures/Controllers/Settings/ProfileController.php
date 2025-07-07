<?php

namespace Tests\Fixtures\Controllers\Settings;

use HardImpact\Waymaker\Get;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProfileController
{
    #[Get(uri: '/settings/profile', middleware: 'auth')]
    public function edit(Request $request): Response
    {
        return response('Profile edit page');
    }
}
