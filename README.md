# omnimap/maplibre

MapLibre for [omniscient/omnimap](https://github.com/glitchr-studio/omnimap), under any PHP host.
Plain PHP: no framework in `require`.

```php
use Omnimap\MapLibre\MapLibreFactory;

$provider = (new MapLibreFactory())->create([/* options: docs/index.md */]);
```

With the family's `Registry`, by its factory name `maplibre`:

```php
$registry = new Omnimap\Registry([new MapLibreFactory()], ['main' => ['factory' => 'maplibre', 'options' => []]]);
```

## Documentation

- [Installation, options, what it answers](docs/index.md)
- [Contributing](CONTRIBUTING.md): the tests, the conformance suite, a release

## Verified, and not

- Verified (`vendor/bin/phpunit`, PHP 8.4): omnimap's conformance suite
  (`Tests/MapLibreContractTest.php`, `Omnimap\Testing\ProviderContractTests`) on recorded answers;
  every class loaded with the package's requirements alone (`BareTest`).
- Not verified yet: the service's live answers. Say here what was run against the real service,
  when, and how (a `live` PHPUnit group, keyless or with keys from the environment).

## Responsibility

omnimap/maplibre is an unofficial client, affiliated with no service it reaches. The MIT licence covers
the code only and grants no right to any service or its data; whoever runs it complies with the
service's terms and obtains what they require.

## Licence

MIT.
