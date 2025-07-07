<?php

use HardImpact\Waymaker\Waymaker;
use HardImpact\Waymaker\Tests\Traits\TestFixtures;

uses(TestFixtures::class);

/**
 * Test the URI generation logic in Waymaker.
 */
test('it correctly generates URIs for different scenarios', function () {
    // Create a reflection method to access the protected method
    $reflectionClass = new ReflectionClass(Waymaker::class);
    $generateUriMethod = $reflectionClass->getMethod('generateUri');
    $generateUriMethod->setAccessible(true);

    // Test cases for generateUri
    $testCases = [
        // prefix, customUri, parameters, controllerName, methodName, expected
        [null, null, null, 'UserController', 'index', '/user'],
        [null, null, null, 'UserController', 'show', '/user/{id}'],
        [null, null, null, 'UserController', 'edit', '/user/{id}'],
        [null, null, null, 'UserController', 'update', '/user/{id}'],
        [null, null, null, 'UserController', 'destroy', '/user/{id}'],
        [null, null, null, 'UserController', 'store', '/user'],
        [null, null, null, 'UserController', 'create', '/user'],
        [null, null, null, 'UserController', 'custom', '/user/custom'],
        ['admin', null, null, 'UserController', 'index', '/admin'],
        [null, 'custom/uri', null, 'UserController', 'index', '/custom/uri'],
        [null, '/custom/uri', null, 'UserController', 'index', '/custom/uri'],
        ['admin', 'custom/uri', null, 'UserController', 'index', '/custom/uri'],
        [null, null, ['id'], 'UserController', 'show', '/user/{id}'],
        [null, null, ['user:uuid'], 'UserController', 'show', '/user/{user:uuid}'],
        ['admin', null, ['id'], 'UserController', 'show', '/admin/{id}'],
        ['admin', null, ['id', 'comment'], 'UserController', 'show', '/admin/{id}/{comment}'],
        ['', null, null, 'UserController', 'index', '/user'],
        [null, '/', null, 'UserController', 'index', '/'],
    ];

    // Create a Waymaker instance
    $waymaker = new Waymaker;

    // Test each case
    foreach ($testCases as [$prefix, $customUri, $parameters, $controllerName, $methodName, $expected]) {
        $uri = $generateUriMethod->invokeArgs(null, [$prefix, $customUri, $parameters, $controllerName, $methodName]);
        expect($uri)->toBe($expected);
    }
});
