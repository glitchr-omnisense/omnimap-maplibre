<?php

namespace Omnimap\MapLibre;

use Omnimap\Exception\ProviderException;
use Omnimap\Exception\UnreachableException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A MapLibre style read on the server (https://maplibre.org/maplibre-style-spec/):
 * its sources, glyphs and sprite, and the hosts they make a visitor's browser
 * reach - what a consent banner and a Content-Security-Policy must name, and
 * what MapLibreProvider::capabilities()->browserHosts should hold.
 */
final readonly class Style
{
    /**
     * @param array<string, mixed>       $style   the style JSON
     * @param array<string, list<string>> $sources each source's tile URLs, its TileJSON read when it has one
     */
    public function __construct(
        public string $url,
        public array $style,
        public array $sources,
    ) {
    }

    /** Reads a style, and the TileJSON of each source that names one. */
    public static function fetch(HttpClientInterface $http, string $url): self
    {
        $style = self::json($http, $url);
        if (8 !== ($style['version'] ?? null) || !\is_array($style['sources'] ?? null)) {
            throw ProviderException::answered('MapLibre', $url, 'something that is not a style (version 8, sources)');
        }
        $sources = [];
        foreach ($style['sources'] as $name => $source) {
            if (!\is_array($source)) {
                continue;
            }
            $tiles = \is_array($source['tiles'] ?? null) ? $source['tiles'] : [];
            if ([] === $tiles && \is_string($source['url'] ?? null) && preg_match('#^https?://#', $source['url'])) {
                $tileJson = self::json($http, $source['url']);
                $tiles = \is_array($tileJson['tiles'] ?? null) ? $tileJson['tiles'] : [];
                $tiles[] = $source['url'];
            }
            $sources[(string) $name] = array_values(array_filter($tiles, 'is_string'));
        }

        return new self($url, $style, $sources);
    }

    /**
     * The hosts the browser reaches: the style's, its sources' (and their TileJSON's), its glyphs', its sprite's.
     *
     * @return list<string>
     */
    public function hosts(): array
    {
        $urls = [$this->url, ...array_merge(...array_values($this->sources) ?: [[]])];
        if (\is_string($this->style['glyphs'] ?? null)) {
            $urls[] = $this->style['glyphs'];
        }
        $sprite = $this->style['sprite'] ?? null;
        foreach (\is_array($sprite) ? $sprite : [$sprite] as $entry) {
            $urls[] = \is_array($entry) ? ($entry['url'] ?? null) : $entry;
        }
        $hosts = [];
        foreach ($urls as $url) {
            $host = \is_string($url) ? parse_url(str_replace(['{fontstack}', '{range}', '{z}', '{x}', '{y}'], 'x', $url), \PHP_URL_HOST) : null;
            if (\is_string($host) && '' !== $host) {
                $hosts[$host] = true;
            }
        }

        return array_keys($hosts);
    }

    /** @return array<string, mixed> */
    private static function json(HttpClientInterface $http, string $url): array
    {
        try {
            $response = $http->request('GET', $url, ['headers' => ['Accept' => 'application/json']]);
            $status = $response->getStatusCode();
            if (200 !== $status) {
                throw ProviderException::answered('MapLibre', $url, $status);
            }
            $body = $response->getContent();
        } catch (HttpException $e) {
            throw new UnreachableException(\sprintf('MapLibre could not reach %s: %s', $url, $e->getMessage()), 0, $e);
        }
        try {
            $json = json_decode($body, true, 64, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ProviderException::answered('MapLibre', $url, 'something that is not JSON');
        }
        if (!\is_array($json) || array_is_list($json)) {
            throw ProviderException::answered('MapLibre', $url, 'a JSON that is not an object');
        }

        /** @var array<string, mixed> $json */
        return $json;
    }
}
