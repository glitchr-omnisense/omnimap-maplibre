<?php

namespace Omnimap\MapLibre;

use Omnimap\Config;
use Omnimap\ProviderFactory;
use Omnimap\ProviderInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MapLibre, from its options:
 *
 *   tiles        the tile URL, {z}/{x}/{y} in it (required)
 *   attribution  as HTML, shown on the map
 *   max_zoom     19
 *   user_agent   sent with every tile the server fetches (images made of tiles)
 *
 * The HTTP client and the cache are the application's, given here: never created inside.
 */
final class MapLibreFactory extends ProviderFactory
{
    public function __construct(
        private readonly ?HttpClientInterface $http = null,
        private readonly ?CacheInterface $cache = null,
    ) {
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnimap.factory_name' => 'maplibre',
            'omnimap.factory_title' => 'MapLibre',
            'omnimap.required_options' => ['tiles'],
            'attribution' => '',
            'max_zoom' => 19,
            'user_agent' => '',
        ]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new MapLibreProvider(
            tiles: (string) $c->string('tiles'),
            attribution: (string) $c->string('attribution'),
            maxZoom: $c->int('max_zoom', 19),
            userAgent: $c->string('user_agent'),
            http: $this->http,
            cache: $this->cache,
        );
    }
}
