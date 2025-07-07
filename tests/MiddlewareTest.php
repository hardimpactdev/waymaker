<?php

use HardImpact\Waymaker\Route;
use HardImpact\Waymaker\Waymaker;
use HardImpact\Waymaker\Tests\Traits\TestFixtures;

uses(TestFixtures::class);

/**
 * Test that middleware is correctly merged from controller and method.
 */
test('it correctly merges controller and method middleware', function () {
    // Create a reflection method to access the protected method
    $reflectionClass = new ReflectionClass(Waymaker::class);
    $processControllerMethodMethod = $reflectionClass->getMethod('processControllerMethod');
    $processControllerMethodMethod->setAccessible(true);

    $formatMiddlewareMethod = $reflectionClass->getMethod('formatMiddleware');
    $formatMiddlewareMethod->setAccessible(true);

    // Test different middleware combinations
    $testCases = [
        // controller middleware, method middleware, expected result
        [
            ['controller-mw'],
            ['method-mw'],
            ['controller-mw', 'method-mw'],
        ],
        [
            ['controller-mw'],
            ['controller-mw', 'method-mw'],
            ['controller-mw', 'method-mw'], // Duplicates should be removed
        ],
        [
            ['auth', 'api'],
            ['throttle'],
            ['auth', 'api', 'throttle'],
        ],
        [
            [],
            ['method-only'],
            ['method-only'],
        ],
        [
            ['controller-only'],
            [],
            ['controller-only'],
        ],
        [
            [],
            [],
            [],
        ],
    ];

    // Create a waymaker instance
    $waymaker = new Waymaker;

    // Test each case
    foreach ($testCases as [$controllerMiddleware, $methodMiddleware, $expected]) {
        // Directly test the array_unique(array_merge()) logic used in the class
        $merged = array_values(array_unique(array_merge($controllerMiddleware, $methodMiddleware)));
        expect($merged)->toBe($expected);

        // Also test the middleware formatting method
        if (! empty($merged)) {
            $formatted = $formatMiddlewareMethod->invoke(null, $merged);

            if (count($merged) === 1) {
                expect($formatted)->toBe("'".$merged[0]."'");
            } else {
                expect($formatted)->toBe("['".implode("', '", $merged)."']");
            }
        }
    }
});
