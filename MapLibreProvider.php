<?php

namespace Omnimap\MapLibre;

use Omnimap\Capabilities;
use Omnimap\Exception\NotSupportedException;
use Omnimap\MapRendererInterface;
use Omnimap\Model\Image;
use Omnimap\Model\MapView;
use Omnimap\Model\Size;
use Omnimap\Static\TileStitcher;
use Omnimap\StaticMapInterface;
use Omnimap\TileSourceInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MapLibre: the live map drawn in the browser by assets/maplibre.js over a
 * tile server, and a view as an image made on the server from the same tiles.
 * The starting point the template gives: replace what the service does
 * differently, keep what the conformance suite checks.
 */
final class MapLibreProvider implements MapRendererInterface, StaticMapInterface, TileSourceInterface
{
    public function __construct(
        private readonly string $tiles,
        private readonly string $attribution = '',
        private readonly int $maxZoom = 19,
        private readonly ?string $userAgent = null,
        private readonly ?HttpClientInterface $http = null,
        private readonly ?CacheInterface $cache = null,
    ) {
    }

    public function getName(): string
    {
        return 'maplibre';
    }

    public function getTitle(): string
    {
        return 'MapLibre';
    }

    public function capabilities(): Capabilities
    {
        $host = parse_url($this->tiles, \PHP_URL_HOST);

        return new Capabilities(
            renderer: true,
            staticMap: null !== $this->http,
            tiles: true,
            browserHosts: \is_string($host) ? [$host] : [],
            cookies: false,
            terms: [],
        );
    }

    public function describe(MapView $view): array
    {
        return [
            'provider' => 'maplibre',
            'consent' => 'MAP',
            'view' => $view->toArray(),
            'tiles' => ['template' => $this->tiles, 'maxZoom' => $this->maxZoom, 'tileSize' => 256],
            'attribution' => $this->attribution,
        ];
    }

    public function fingerprint(MapView $view): string
    {
        return 'maplibre|'.json_encode([$this->tiles, $view->center, $view->zoom, $view->markers, $view->controls], \JSON_THROW_ON_ERROR);
    }

    public function attribution(): string
    {
        return $this->attribution;
    }

    public function image(MapView $view, Size $size, int $scale = 1): Image
    {
        if (null === $this->http) {
            throw new NotSupportedException('No HTTP client was given to the MapLibre provider: no image made of tiles.');
        }

        return (new TileStitcher($this, $this->http, $this->cache))->image($view, $size, $scale);
    }

    public function template(): string
    {
        return $this->tiles;
    }

    public function url(int $zoom, int $x, int $y): string
    {
        return str_replace(['{z}', '{x}', '{y}'], [(string) $zoom, (string) $x, (string) $y], $this->tiles);
    }

    public function tileSize(): int
    {
        return 256;
    }

    public function maxZoom(): int
    {
        return $this->maxZoom;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return array_filter(['User-Agent' => (string) $this->userAgent]);
    }
}
