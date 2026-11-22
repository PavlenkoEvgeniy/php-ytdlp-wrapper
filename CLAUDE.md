# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

`p3sdev/php-ytdlp-wrapper` is a reusable PHP library and Symfony bundle that wraps the `yt-dlp`
CLI through `symfony/process`. It targets PHP 8.4-8.6 and supports Symfony 7.4 and 8.x. It is not an
application: there is no Doctrine, HTTP UI, Twig, console commands, or messenger transport.

The bundle is used by production projects. Within the 1.x line the public API, configuration keys and
defaults, option semantics, and behavior for valid input must stay backward compatible. Additive
changes and security fixes for malicious input are allowed; breaking changes are deferred to 2.0.

## Commands

```bash
composer update                 # the library lock is untracked; use update, not install
make test                       # PHPUnit
vendor/bin/phpunit --filter YtDlpClientTest
make lint                       # php-cs-fixer + PHPStan + Psalm + peck
make php-cs-fixer-check         # or phpstan, psalm, peck individually
composer validate --strict
composer audit
```

`php-cs-fixer` 3.95 supports syntax only up to PHP 8.5. CI runs it on PHP 8.5; the Makefile passes
`--allow-unsupported-php-version=yes` so `make lint` also works on a local PHP 8.6 host.

The development toolchain (Psalm 7.16) requires PHP 8.4.3+ even though the runtime constraint is
`^8.4`; CI uses the latest 8.4 patch.

OpenSpec is the planning source of truth:

```bash
npx --yes @fission-ai/openspec@1.16.0 list --json
npx --yes @fission-ai/openspec@1.16.0 status --change "<change>" --json
npx --yes @fission-ai/openspec@1.16.0 validate --all --strict
```

The Claude workflows are `/opsx:propose`, `/opsx:apply`, `/opsx:archive`, `/opsx:update`,
`/opsx:sync`, and `/opsx:explore`. Specs describe observable behavior; PHP style rules do not belong
in them.

## Architecture

The flow is: bundle -> extension -> configuration tree -> service definition -> client.

- `YtDlpBundle` exposes `YtDlpExtension`; the extension processes the `yt_dlp` configuration tree and
  registers services from `config/services.php`.
- `Configuration` defines `binary_path` (`yt-dlp`), `default_arguments` (`--no-warnings`),
  `working_directory` (`null`), `timeout` (`300.0`), and `environment` (`[]`).
- `YtDlpClient` resolves the binary, builds the argv, runs it without a shell, and returns a
  `ProcessResult`. `YtDlpRequest` is the immutable request DTO. Exceptions are `YtDlpException`,
  `BinaryNotFoundException`, and `ProcessFailedException` (which carries the result).
- Binary resolution: an explicit path must exist and be executable; the default name is looked up in
  the configured `environment.PATH` (falling back to the host `PATH`). The custom scan is intentional:
  Symfony's `ExecutableFinder` cannot accept a configured PATH. Do not replace it without a design
  change.
- Command construction order: binary, default arguments, flags, options, extra arguments, `--`, URLs.
  Option names without a leading dash get `--`; array values repeat the option; `true` emits the flag,
  `false`/`null` omit it. The `--` marker is a security boundary: it prevents URL-shaped input from
  being parsed as an option. Option keys and extra arguments are trusted input.
- `ProcessResult` captures exit code, stdout, stderr, the command, and JSON objects decoded line by
  line. `extractInfo()` returns only those decoded objects.

## Compatibility and deferred work

- Runtime constraints: `php: ^8.4`, Symfony `^7.4 || ^8.0`. CI tests PHP 8.4/8.5/8.6 across
  lowest/highest dependencies; `lowest` exercises Symfony 7.4 and `highest` exercises Symfony 8.1.
  Symfony 8.0 is end-of-life and blocked by Composer advisories; do not add a permanent 8.0 CI cell.
- Deferred to 2.0: earlier empty-URL validation, wrapping process timeouts, signal-derived exit
  codes, deduplicating `--no-warnings`, removing that flag from `rawJson()`, stricter configuration
  typing, a process-factory seam, and replacing the custom PATH scan.

## Code rules

The user's rulebook is external: `$TASKS_DIR/_code-style` (`README.md`, `comments.md`, `php.md`,
`php/*.md`). Apply it with the library exceptions recorded in the OpenSpec design:

- PHP service configuration and `Extension` parameters instead of YAML/attribute wiring.
- No required `#[Autowire]`/`#[AsAlias]`; bundle configuration flows through parameters.
- Custom PATH scan instead of `ExecutableFinder`.
- English comments and test PHPDoc, because this is a public English-language OSS repository.
- `final readonly class` is considered backward compatible for classes that are already `final`.

Tests use a generated shell-script fake binary; there is no process factory. Use `test.local` in test
data, not `example.com`.

## Working conventions

- Work on a separate branch. Commits and pushes in this repository are made by the user; Claude edits
  files and runs checks.
- Session journal: `$TASKS_DIR/ytdownloader/_local.md`. OpenSpec `tasks.md` is the canonical plan for
  an active change; the journal links to it rather than duplicating it.
- OpenSpec project configuration and artifact rules live in `openspec/config.yaml`.
