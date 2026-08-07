<?php

namespace NckRtl\Waymaker;

use Attribute;
use NckRtl\Waymaker\Enums\HttpMethod;

#[Attribute]
class Get extends RouteAttribute
{
    public function __construct(
        ?string $uri = null,
        ?string $name = null,
        ?array $parameters = null,
        array|string|null $middleware = null,
        ?string $middlewareGroup = null,
    ) {
        $this->method = HttpMethod::GET;
        parent::__construct(
            $uri,
            $name,
            $parameters,
            $middleware,
            $middlewareGroup,
        );
    }
}
