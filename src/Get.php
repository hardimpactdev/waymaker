<?php

namespace HardImpact\Waymaker;

use Attribute;
use HardImpact\Waymaker\Enums\HttpMethod;

#[Attribute]
class Get extends RouteAttribute
{
    public function __construct(
        ?string $uri = null,
        ?string $name = null,
        ?array $parameters = null,
        array|string|null $middleware = null,
    ) {
        $this->method = HttpMethod::GET;
        parent::__construct(
            $uri,
            $name,
            $parameters,
            $middleware,
        );
    }
}
