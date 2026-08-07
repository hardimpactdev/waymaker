<?php

namespace NckRtl\Waymaker;

use Attribute;
use NckRtl\Waymaker\Enums\HttpMethod;

#[Attribute]
class Delete extends RouteAttribute
{
    public function __construct(
        ?string $uri = null,
        ?string $name = null,
        ?array $parameters = null,
        array|string|null $middleware = null,
        ?string $middlewareGroup = null,
    ) {
        $this->method = HttpMethod::DELETE;
        parent::__construct(
            $uri,
            $name,
            $parameters,
            $middleware,
            $middlewareGroup,
        );
    }
}
