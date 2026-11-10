<?php

namespace Omnimap\MapLibre;

use Omnimap\Capabilities;
use Omnimap\Exception\NotSupportedException;
use Omnimap\MapRendererInterface;
use Omnimap\Model\MapView;
use Omnisense\Contracts\Policy\DeclaresUsagePolicyInterface;
use Omnisense\Contracts\Policy\UsagePolicy;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MapLibre: the live map drawn by MapLibre GL JS (assets/maplibre.js) from a
 * vector style - OpenFreeMap's by default, any MapLibre style by its URL.
 *
 * Only a renderer: vector tiles are drawn by the browser's WebGL, not by PHP,
 * so the server makes no image of a view (no StaticMapInterface) and serves no
 * raster tiles (no TileSourceInterface). Its picture still reaches omnimap's
 * map cache, captured in the browser (assets/capture.js; the map keeps its
 * drawing buffer for it).
 *
 * MapView::options('maplibre')['map'] - MapLibre's map options, as JSON - is
 * the only view option of its own.
 *
 * Its usage policy is the style's source's: OpenFreeMap's (policy.json) for
 * its styles; for any other, unknown, and the site declares that source's
 * terms (the "policy" option).
 */
final class MapLibreProvider implements MapRendererInterface, DeclaresUsagePolicyInterface
{
    /** OpenFreeMap's terms, as data: the default style's source. */
    public const POLICY = __DIR__.'/policy.json';

    private static ?UsagePolicy $policy = null;

    /**
     * @param list<string>          $hosts
     * @param array<string, string> $library
     */
    public function __construct(
        private readonly string $style = MapLibreFactory::OPENFREEMAP_STYLE,
        private readonly string $attribution = MapLibreFactory::OPENFREEMAP_ATTRIBUTION,
        private readonly array $hosts = [],
        private readonly array $library = [],
        private readonly bool $preserveDrawingBuffer = true,
        private readonly ?HttpClientInterface $http = null,
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
        $hosts = [...($this->hosts ?: [parse_url($this->style, \PHP_URL_HOST)]), parse_url($this->library['script'] ?? '', \PHP_URL_HOST), parse_url($this->library['module'] ?? '', \PHP_URL_HOST), parse_url($this->library['style'] ?? '', \PHP_URL_HOST)];

        return new Capabilities(
            renderer: true,
            browserHosts: array_values(array_unique(array_filter($hosts, static fn ($host) => \is_string($host) && '' !== $host))),
            cookies: false,
            terms: str_starts_with($this->style, 'https://tiles.openfreemap.org/')
                ? ['https://openfreemap.org/tos/', 'https://www.openstreetmap.org/copyright', 'https://github.com/openmaptiles/openmaptiles/blob/master/LICENSE.md']
                : ['https://www.openstreetmap.org/copyright'],
            policy: $this->usagePolicy(),
        );
    }

    /**
     * OpenFreeMap's terms for its styles; for a style of another source,
     * nothing known: that source's limits and rights are the site's to
     * declare.
     */
    public function usagePolicy(): UsagePolicy
    {
        if (str_starts_with($this->style, 'https://tiles.openfreemap.org/')) {
            return self::$policy ??= UsagePolicy::fromFile(self::POLICY);
        }

        return UsagePolicy::builder('maplibre')
            ->provenance(notes: 'A style of the site\'s choice (MapTiler, Mapbox, its own tiles): that source\'s terms, limits, rights and attribution are its own, which the site declares (the "policy" option); the attribution option is what the map shows.')
            ->build();
    }

    public function describe(MapView $view): array
    {
        return [
            'provider' => 'maplibre',
            'consent' => 'MAP',
            'view' => $view->toArray(),
            'library' => $this->library,
            'style' => $this->style,
            'options' => ['map' => $view->options('maplibre')['map'] ?? new \stdClass(), 'preserveDrawingBuffer' => $this->preserveDrawingBuffer],
            'attribution' => $this->attribution,
        ];
    }

    public function fingerprint(MapView $view): string
    {
        return 'maplibre|'.json_encode([$this->style, $view->center, $view->zoom, $view->markers, $view->controls, $view->options('maplibre')], \JSON_THROW_ON_ERROR);
    }

    public function attribution(): string
    {
        return $this->attribution;
    }

    /** The style's URL. */
    public function style(): string
    {
        return $this->style;
    }

    /**
     * The style read on the server: what it makes the browser reach (Style::hosts()).
     *
     * @throws NotSupportedException without the factory's HTTP client
     */
    public function inspect(): Style
    {
        return Style::fetch($this->http ?? throw new NotSupportedException('No HTTP client was given to the MapLibre provider: the style is not read on the server.'), $this->style);
    }
}
