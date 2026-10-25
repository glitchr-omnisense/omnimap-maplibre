<?php

namespace Omnimap\MapLibre\Tests;

use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\ProviderFactoryInterface;
use Omnimap\Testing\ProviderContractTests;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * omnimap's conformance suite on MapLibre, with its default options (OpenFreeMap's style, MapLibre GL JS
 * from unpkg): a renderer only - no request made on the server to describe a view.
 */
final class MapLibreContractTest extends TestCase
{
    use ProviderContractTests;

    protected function contractFactory(?HttpClientInterface $http): ProviderFactoryInterface
    {
        return new MapLibreFactory($http);
    }
}
