<?php

use HardImpact\Waymaker\Enums\HttpMethod;
use HardImpact\Waymaker\Waymaker;
use HardImpact\Waymaker\Tests\Traits\TestFixtures;

uses(TestFixtures::class);

/**
 * Test that HTTP method defaults work correctly based on method names.
 */
test('it applies correct HTTP method defaults based on method names', function () {
    // Create a reflection method to access the protected getMethodDefault method
    $reflectionClass = new ReflectionClass(Waymaker::class);
    $getMethodDefaultMethod = $reflectionClass->getMethod('getMethodDefault');
    $getMethodDefaultMethod->setAccessible(true);

    // Set up configurations for the test
    config(['waymaker.method_defaults' => [
        'GET' => ['index', 'show'],
        'POST' => ['store'],
        'PUT' => ['update'],
        'DELETE' => ['destroy'],
        'PATCH' => ['edit'],
    ]]);

    // Create an instance of Waymaker to invoke the method
    $waymaker = new Waymaker;

    // Test each method name default
    $methodDefaults = [
        'index' => HttpMethod::GET,
        'show' => HttpMethod::GET,
        'store' => HttpMethod::POST,
        'update' => HttpMethod::PUT,
        'destroy' => HttpMethod::DELETE,
        'edit' => HttpMethod::PATCH,
        'something_else' => null, // Should return null for unknown methods
    ];

    foreach ($methodDefaults as $methodName => $expectedMethod) {
        $result = $getMethodDefaultMethod->invoke(null, $methodName);

        if ($expectedMethod === null) {
            expect($result)->toBeNull();
        } else {
            expect($result)->toBeInstanceOf(HttpMethod::class);
            expect($result)->toBe($expectedMethod);
        }
    }
});
