# Contributing a provider

How a provider of an omni family is written, tested, documented and released. The same steps for
every family; what differs is the contract and its conformance suite, named in the family core's
`docs/providers.md` (or its equivalent).

## 1. Start

```sh
gh repo create <owner>/<family>-<name> --public --template glitchr-studio/omni-provider-template --clone
cd <family>-<name>
bin/init <family> <name> "<Title>"
composer install
vendor/bin/phpunit
```

The suite is green before you write a line: the skeleton is a minimal provider that meets the
contract. Commit it as it is, then make it the service's.

No PHP on your machine? Run the same in the CI image:

```sh
docker run --rm -v "$PWD":/app -w /app ghcr.io/glitchr-studio/php:8.4 sh -c 'composer install && vendor/bin/phpunit'
```

## 2. Implement

- The package is plain PHP: no framework in `require`. HTTP clients (Symfony's contracts, PSR-18)
  and caches come through the factory's constructor, never created inside. The Symfony and Laravel
  wiring lives in omnibridge, not here.
- The factory names the provider (its factory name is the configuration's `factory`), lists the
  required options and their defaults. A missing required option is the family's configuration
  exception, naming it.
- Implement the contracts the service can answer, say so in `capabilities()` / `supports()`, and
  refuse the others with the family's "not supported" exception - never a `TypeError`, never a
  silent `null`.
- Errors follow the family's rule (its core's docs): the service's refusals (401, 403, 404, 429,
  5xx) and unreadable answers (a proxy's HTML page in a 200) are the family's provider exception,
  a request that got no answer its unreachable one. Keep the service's message.
- Respect the service's terms in code: its rate limits, its required User-Agent, its attribution.
  List its terms' addresses where the family asks for them.

## 3. Test

- `Tests/<Name>ContractTest.php` uses the family's conformance trait (`<Core>\Testing\*ContractTests`).
  It runs offline, on recorded answers: give the suite the service's real answers (recorded
  once, or written from the service's documentation when no key is at hand - say which in the
  fixture's docblock). The compatibility dashboard of the omniscient site counts a package as
  running the suite when a test class holds `use <Core>\Testing\<Something>ContractTests;`.
- A check the service legitimately cannot meet becomes the trait's optional hook, documented -
  not a skipped test, not a fake pass. A check it fails is a defect: fix it.
- Your own tests beside it: what is particular to the service (its URL, its signature, its paging).
- `BareTest` proves every class loads with the package's requirements alone.
- Live checks, when the service is reachable without a key or with one from the environment, go in
  the PHPUnit group `live` (excluded by default): `vendor/bin/phpunit --group live`.
- PHPStan at the highest level: `phpstan analyse`. What `phpstan-baseline.neon` lists is accepted;
  anything new fails the CI. Keep the baseline empty in a new package.

## 4. Document

- `README.md`: what the provider is, its smallest use, and **Verified, and not**: what was run
  (the suite, offline; the live group, on which date, against what), and what was not. Never
  claim what no test ran.
- `docs/index.md`: installation, every option with its default, what it answers and what it
  refuses, its errors, the service's terms.
- `CHANGELOG.md`: every change under `## Unreleased` as you go.

## 5. Release

1. Move the `## Unreleased` entries under `## [X.Y.Z] - YYYY-MM-DD`, commit, push, and wait for the
   branch's CI to be green.
2. Tag and push the tag:

   ```sh
   git tag -a vX.Y.Z -m "X.Y.Z"
   git push origin vX.Y.Z
   ```

3. `.github/workflows/release.yml` runs the package's suite on the tag, then
   glitchr-studio/ci's `php-release.yml`: the tag is checked as a version, the changelog's
   section becomes the release's notes (no section, no release), the tag's source archive is
   attached with its SHA-256 and a build provenance attestation
   (`gh attestation verify <zip> --repo <owner>/<repo>`), and the GitHub release is published -
   a pre-release for `vX.Y.Z-rc.1` or any `0.x`.
4. Packagist: submit the repository once (https://packagist.org/packages/submit); afterwards
   Packagist's GitHub integration picks tags up, or the release workflow tells it when the
   repository holds `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` secrets.

A published tag is never moved or deleted: a mistake is a new version.

## 6. Make it known

Ask the family's maintainers to list the provider in the core's README and `suggest`, and the
omniscient site's catalogue (`catalog/<family>.yaml`) picks it up for the package pages and the
compatibility dashboard.
