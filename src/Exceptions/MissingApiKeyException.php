<?php

namespace Cabuz\Tembo\Exceptions;

class MissingApiKeyException extends TemboException
{
    public static function make(): self
    {
        return new self('No Tembo API key configured. Set TEMBO_API_KEY in your .env file or call Tembo::withApiKey().');
    }
}
