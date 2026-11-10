<?php

namespace Omnimap\MapLibre\Tests;

use Omnimap\Exception\UsageLimitReachedException;
use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\MapLibre\MapLibreProvider;
use Omnimap\MapRendererInterface;
use Omnimap\Model\LatLng;
use Omnimap\Model\MapView;
use Omnimap\Registry;
use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\PolicyExport;
use Omnisense\Contracts\Policy\UsagePolicy;
use PHPUnit\Framework\TestCase;

/** OpenFreeMap's terms as policy.json declares them, and a style of another source. */
final class PolicyTest extends TestCase
{
    public function testThePolicyFileReadsAsOpenFreeMapsTermsSayThem(): void
    {
        $policy = UsagePolicy::fromFile(MapLibreProvider::POLICY);
        $declared = (new PolicyExport())->folders(\dirname(__DIR__))->entries();

        self::assertSame(['package' => 'omnimap/maplibre', 'version' => null, 'file' => 'policy.json', 'policy' => $policy->toArray()], $declared[0] ?? null, 'composer.json names it (extra.omnisense.policy)');
        self::assertSame('maplibre', $policy->provider);
        self::assertSame('2026-11-10', $policy->provenance->verifiedAt);
        self::assertSame([], $policy->rateLimits, 'no limits on map views or requests');
        self::assertSame(Permission::Allowed, $policy->rights->commercial);
        self::assertFalse($policy->requirements->key);
        self::assertSame('OpenFreeMap © OpenMapTiles Data from OpenStreetMap', $policy->rights->attribution->text);

        self::assertEquals($policy, (new MapLibreFactory())->create()->capabilities()->policy());
    }

    public function testAStyleOfAnotherSourceIsUnknownAndTheSitesLimitCounted(): void
    {
        $registry = new Registry([new MapLibreFactory()], ['maps' => [
            'factory' => 'maplibre',
            'options' => ['style' => 'https://api.maptiler.example/maps/streets/style.json'],
            'policy' => ['rateLimits' => [['max' => 1, 'window' => 'month', 'operations' => ['describe'], 'note' => 'the source\'s map loads']]],
        ]]);
        $policy = $registry->policy('maps');

        self::assertCount(1, $policy->rateLimits ?? []);
        self::assertSame(Permission::Unknown, $policy->rights->commercial);

        $renderer = $registry->guarded('maps', MapRendererInterface::class);
        $renderer->describe(new MapView(new LatLng(0, 0), 2));
        $this->expectException(UsageLimitReachedException::class);
        $renderer->describe(new MapView(new LatLng(0, 0), 2));
    }
}
