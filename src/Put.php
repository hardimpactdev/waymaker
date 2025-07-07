<?php

namespace HardImpact\Waymaker;

use Attribute;
use HardImpact\Waymaker\Enums\HttpMethod;

#[Attribute]
class Put extends RouteAttribute
{
    public function __construct(
        public ?string $uri = null,
        public ?string $name = null,
        public ?array $parameters = null,
        public array|string|null $middleware = null,
    ) {
        $this->method = HttpMethod::PUT;
        parent::__construct(
            $uri,
            $name,
            $parameters,
            $middleware,
        );
    }
}
