<?php

namespace Omnimap\MapLibre\Tests;

use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\ProviderFactoryInterface;
use Omnimap\Testing\ArrayCache;
use Omnimap\Testing\ProviderContractTests;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** omnimap's conformance suite on MapLibre, offline: the tiles from the suite's recorded PNG. */
final class MapLibreContractTest extends TestCase
{
    use ProviderContractTests;

    protected function contractFactory(?HttpClientInterface $http): ProviderFactoryInterface
    {
        return new MapLibreFactory($http, new ArrayCache());
    }

    /** @return array<string, mixed> */
    protected function contractOptions(): array
    {
        return ['tiles' => 'https://tiles.example.org/{z}/{x}/{y}.png', 'attribution' => '&copy; Example', 'user_agent' => 'contract-tests/1.0'];
    }
}
