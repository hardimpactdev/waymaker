<?php

use HardImpact\Waymaker\Enums\HttpMethod;
use HardImpact\Waymaker\Tests\Factories\ControllerFactory;
use HardImpact\Waymaker\Tests\Traits\TestFixtures;

uses(TestFixtures::class);

/**
 * Test the controller factory for creating test controllers.
 */
test('it can generate controllers dynamically for testing', function () {
    // Create a dynamic controller using the factory
    $factory = ControllerFactory::create('DynamicController')
        ->withRoutePrefix('dynamic')
        ->withMiddleware(['auth', 'api'])
        ->addMethod('index')  // Will use GET by default
        ->addMethod('show', null, ['id'])  // GET with parameter
        ->addMethod('store', HttpMethod::POST, null, 'verified')  // POST with middleware
        ->addMethod('custom', HttpMethod::PUT, ['id'], ['throttle', 'cache'], 'custom.route', 'custom-uri');

    // Get the generated code
    $code = $factory->generate();

    // Check namespace and class structure
    expect($code)->toMatch('/namespace HardImpact\\\\Waymaker\\\\Tests\\\\Http\\\\Controllers\\\\temp;/');
    expect($code)->toMatch('/class DynamicController extends Controller/');

    // Check controller properties
    expect($code)->toMatch('/protected static string \$routePrefix = \'dynamic\';/');
    expect($code)->toMatch('/protected static array \$routeMiddleware = \[\'auth\', \'api\'\];/');

    // Check method generation
    expect($code)->toMatch('/public function index\(\): Response/');
    expect($code)->toMatch('/public function show\(\$param\): Response/');

    // Check attribute usage
    expect($code)->toMatch('/#\[Post\(middleware: \'verified\'\)\]/');
    expect($code)->toMatch('/#\[Put\(parameters: \[\'id\'\], middleware: \[\'throttle\', \'cache\'\], name: \'custom\.route\', uri: \'custom-uri\'\)\]/');

    // Check method content - return statements
    expect($code)->toMatch('/return inertia\(\'Index\'\);/');
    expect($code)->toMatch('/return inertia\(\'Show\', \[\s*\'param\' => \$param,\s*\]\);/');
});
