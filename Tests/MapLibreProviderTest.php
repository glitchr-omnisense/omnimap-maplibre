<?php

namespace Omnimap\MapLibre\Tests;

use Omnimap\Exception\NotSupportedException;
use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\MapLibre\MapLibreProvider;
use Omnimap\Model\LatLng;
use Omnimap\Model\MapView;
use Omnimap\Model\Marker;
use Omnimap\Registry;
use PHPUnit\Framework\TestCase;

final class MapLibreProviderTest extends TestCase
{
    public function testTheDescriptionMapLibreDrawsWithItsDefaults(): void
    {
        $provider = (new MapLibreFactory())->create();
        \assert($provider instanceof MapLibreProvider);
        $view = new MapView(new LatLng(48.5734, 7.7521), 13, [new Marker(new LatLng(48.5818, 7.7509), 'Cathédrale')], id: 'strasbourg');
        $description = $provider->describe($view);

        self::assertSame('maplibre', $description['provider']);
        self::assertSame('https://tiles.openfreemap.org/styles/liberty', $description['style']);
        self::assertSame([
            'script' => 'https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js',
            'scriptIntegrity' => MapLibreFactory::LIBRARY_SCRIPT_INTEGRITY,
            'style' => 'https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css',
            'styleIntegrity' => MapLibreFactory::LIBRARY_STYLE_INTEGRITY,
        ], $description['library']);
        self::assertIsArray($description['options']);
        self::assertTrue($description['options']['preserveDrawingBuffer'], 'capture.js can take its picture');
        self::assertSame(['tiles.openfreemap.org', 'unpkg.com'], $provider->capabilities()->browserHosts);
        self::assertSame(['renderer'], $provider->capabilities()->contracts());
        self::assertFalse($provider->capabilities()->cookies);
        self::assertContains('https://openfreemap.org/tos/', $provider->capabilities()->terms);
        self::assertStringContainsString('OpenStreetMap', $provider->attribution());
    }

    public function testAStyleOfOnesOwnAndMapLibreAsAModule(): void
    {
        $registry = new Registry([new MapLibreFactory()], ['maps' => ['factory' => 'maplibre', 'options' => [
            'style' => 'https://maps.example.org/styles/dark.json',
            'hosts' => 'maps.example.org, tiles.example.org',
            'attribution' => '&copy; Example',
            'library_module' => 'https://assets.example.org/maplibre-gl@6.13.0/maplibre-gl.mjs',
            'library_style' => 'https://assets.example.org/maplibre-gl@6.13.0/maplibre-gl.css',
            'library_style_integrity' => '',
            'preserve_drawing_buffer' => false,
        ]]]);
        $provider = $registry->renderer('maps');
        $description = $provider->describe(new MapView(new LatLng(0, 0), 2));

        self::assertSame(['module' => 'https://assets.example.org/maplibre-gl@6.13.0/maplibre-gl.mjs', 'style' => 'https://assets.example.org/maplibre-gl@6.13.0/maplibre-gl.css'], $description['library']);
        self::assertIsArray($description['options']);
        self::assertFalse($description['options']['preserveDrawingBuffer']);
        self::assertSame(['maps.example.org', 'tiles.example.org', 'assets.example.org'], $provider->capabilities()->browserHosts);
        self::assertSame(['https://www.openstreetmap.org/copyright'], $provider->capabilities()->terms);
    }

    public function testThePictureFollowsTheStyleAndTheViewsMapLibreOptions(): void
    {
        $view = new MapView(new LatLng(48.5734, 7.7521), 13);
        $liberty = (new MapLibreFactory())->create();
        $positron = (new MapLibreFactory())->create(['style' => 'https://tiles.openfreemap.org/styles/positron']);
        \assert($liberty instanceof MapLibreProvider && $positron instanceof MapLibreProvider);

        self::assertNotSame($liberty->fingerprint($view), $positron->fingerprint($view));
        self::assertNotSame($liberty->fingerprint($view), $liberty->fingerprint($view->withOptions('maplibre', ['map' => ['pitch' => 45]])));
        $options = $liberty->describe($view->withOptions('maplibre', ['map' => ['pitch' => 45]]))['options'];
        self::assertIsArray($options);
        self::assertSame(['pitch' => 45], $options['map']);
    }

    public function testTheStyleIsNotReadWithoutAClient(): void
    {
        $provider = (new MapLibreFactory())->create();
        \assert($provider instanceof MapLibreProvider);

        $this->expectException(NotSupportedException::class);
        $provider->inspect();
    }
}
