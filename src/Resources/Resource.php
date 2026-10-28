<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Tembo;

abstract class Resource
{
    public function __construct(protected Tembo $tembo) {}

    /**
     * Build a path, URL-encoding every parameter.
     */
    protected function path(string $format, string|int ...$params): string
    {
        return vsprintf($format, array_map(static fn ($param) => rawurlencode((string) $param), $params));
    }
}
