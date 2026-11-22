# Design

## Context

The bundle is a thin wrapper around the `yt-dlp` binary. Its public surface is small: a client
interface with four operations, a request value object, a result value object, three exception types
(a base and two concrete), a configuration tree, and one service definition. The implementation already uses array-mode
`symfony/process`, so there is no shell injection surface; the remaining gap is that URLs are appended
without an end-of-options marker. See `proposal.md` for motivation and `specs/yt-dlp-client/spec.md`
for the behavior contract.

Constraints that shape the approach:

- The bundle is consumed by production projects; the 1.x line must stay backward compatible.
- Symfony 8.0 is end-of-life and every 8.0.x release of `symfony/config` and `symfony/http-kernel` is
  blocked by Composer's security-advisory policy. The current 8.x line is 8.1.
- PHP 8.6 is installed locally; PHP 8.4 and 8.5 are only available through CI.
- The rulebook lives outside the repository in `$TASKS_DIR/_code-style`; it targets applications, so
  some rules need a recorded library exception.

## Goals / Non-Goals

**Goals:**

- Add Symfony 8.x and PHP 8.4-8.6 support without changing the public API or valid-input behavior.
- Make the project installable and verifiable on PHP 8.6, including static analysis and lint.
- Close the argument-injection gap for URL input.
- Bring the implementation and tooling in line with the rulebook, with exceptions documented.
- Establish OpenSpec as the planning source of truth and a capability spec for the wrapper.

**Non-Goals:**

- No breaking API, configuration, or behavior changes; those are deferred to 2.0.
- No new abstraction over `Process` and no change to the custom PATH resolution.
- No permanent CI cell for Symfony 8.0, and no attempt to keep 8.0 installable under the advisory
  blocker.
- No application-level capabilities (Doctrine, controllers, Twig, console commands).

## Decisions

- **Initialize OpenSpec in this repository with the standard `spec-driven` schema.** The user asked to
  work through OpenSpec; the neighbouring `php-symfony-ytdownloader` already uses it. We ran
  `npx @fission-ai/openspec@1.16.0 init . --tools claude --language en --no-animation`, which creates
  `openspec/`, six skills, and six `opsx` commands. The version is pinned because the CLI is not
  installed globally. Alternative: keep the change in the sibling repository — rejected because the
  code lives here and the spec would be separated from it.

- **Declare Symfony compatibility as `^7.4 || ^8.0` and test 7.4 and 8.1.** `^8.0` is the honest
  compatibility declaration and resolves 8.1.11 today. A permanent 8.0 CI cell would require disabling
  Composer's advisory blocker and conflicts with php-cs-fixer 3.95, so 8.0 compatibility is verified
  once during implementation and then covered by the 8.1 highest cell. Alternative: hard floors
  (`^7.4.23 || ^8.1.11`) — rejected because it silently drops the literal 8.0 the user asked for and
  makes the constraint harder to explain.

- **Stop tracking `composer.lock`.** It is a library: consumers ignore the lock, while CI needs to
  resolve different dependency sets per matrix cell. CI runs `composer update` in every cell. The lock
  is added to `.gitignore`. Alternative: keep and regenerate on PHP 8.4 — rejected because it pins the
  matrix and can force PHPUnit/Psalm floors that fail on 8.4.0.

- **Raise development tools to the minimum that supports PHP 8.6.** Psalm 7.16 is mandatory: Psalm 6
  cannot be installed on 8.6. PHPStan 2.3.7 has explicit 8.6 support, peck 0.3 accepts Symfony 8,
  and PHPUnit 11.5.58 was verified on 8.6. PHPUnit stays on the 11.x line to keep the diff small; a
  major upgrade is a separate change. Alternative: PHPUnit 13 — rejected for noise, not capability.

- **Insert `--` before URLs, and only there.** The command builder adds the marker when the last
  extra argument is not already `--`. Option keys and extra arguments remain trusted input and are
  documented as such. URLs are not rejected for starting with `-`, because the marker already makes
  them positional and rejection would be a needless behavioral restriction. Alternative: reject
  leading dashes — rejected as a BC-reducing belt-and-braces measure with no extra protection.

- **Keep the 1.x contract frozen.** Public signatures, configuration keys and defaults, option
  semantics, and valid-input behavior do not change. The following are explicitly deferred to 2.0:
  earlier empty-URL validation, wrapping process timeouts in a wrapper exception, signal-derived exit
  codes, deduplicating `--no-warnings`, removing that flag from the metadata operation, stricter
  configuration typing, a process-factory seam, and replacing the custom PATH scan. Also deferred:
  resolving a relative explicit binary path against `working_directory` (validation currently uses
  the PHP working directory) and wrapping non-exit process failures other than a timeout (for example
  an invalid working directory).

- **Keep the custom PATH scan.** The rulebook prefers framework helpers over hand-rolled code, but
  `Symfony\Component\Process\ExecutableFinder` reads only the host `PATH` and has no parameter for the
  configured environment. Keeping the scan preserves the documented `environment.PATH` behavior and
  the existing test. This exception is recorded here rather than treated as an oversight.

- **Pass environment values through to Symfony Process unchanged.** Process defines `EnvArray`
  semantics: `false` omits a variable, `null` becomes an empty value, and `Stringable` is
  stringified. Casting every value to a string would change valid 1.0 behavior and reject
  `Stringable`, so the client passes the array through. Default arguments are cast to strings
  because the generated command is a public `string[]`. Alternative: normalize every environment
  value to a string — rejected as a backward-compatibility regression.

- **Use PHP service configuration and `Extension` parameters.** The rulebook prefers YAML
  configuration and attribute-based wiring for applications. A reusable bundle must expose parameters
  through `Configuration` and `Extension`, and attribute wiring cannot carry bundle configuration into
  a consumer application. This is a recorded library exception. Attributes such as `#[Autowire]` and
  `#[AsAlias]` are not required here.

- **Treat `final readonly class` as backward compatible.** The affected classes are already `final`,
  their promoted properties are already `readonly`, and no legitimate consumer can assign dynamic
  properties or subclass them. Reflection-only differences do not affect the documented contract.

- **Record English comments and test PHPDoc as a project exception.** The rulebook defaults to
  Russian, but this is a public OSS repository whose README and package metadata are English. The
  neighbouring project already records an English-language exception; this bundle extends it.

- **CI layout: six test cells plus three focused jobs.** The test matrix is PHP
  `8.4/8.5/8.6` x `lowest/highest`; `lowest` exercises Symfony 7.4, `highest` exercises Symfony 8.1.
  Static analysis runs on PHP 8.6 with highest dependencies because Psalm on a lowest-resolved
  dependency set fails on old `amphp/*` implicit-nullable deprecations. Lint runs on PHP 8.5 because
  php-cs-fixer 3.95 only understands syntax up to 8.5. A coverage job on PHP 8.6 requires 100% line
  coverage. Alternative: one cell per PHP version — rejected because it would never exercise the
  declared 7.4 floor.

- **Keep the fake-binary test strategy.** A shell-script fake already covers argv construction and
  real process execution. Injecting a process factory would add a public seam only for mockability;
  the rulebook's YAGNI and the bundle's size argue against it.

## Code-rule compliance (library exceptions recorded)

Mechanical divergences found by the audit and their resolution:

- `php.md` §16: convert the three value/service classes to `final readonly class`.
- `php.md` §11: move `#[\Override]` above the docblock in the extension and drop the redundant
  `@param` that restates the parent signature.
- `php.md` §1: replace the object-to-null comparison in the bundle with an `instanceof` check and
  remove the now-redundant second check.
- `base.md` PHPDoc: replace `list<T>` annotations with `T[]` / `array{...}[]`.
- `base.md` strings/chains/arguments: use `\sprintf` instead of concatenation, put `;` on its own
  line in multi-call chains, add trailing commas to multiline calls, and keep blank lines around
  object construction.
- `linting.md`: remove `--show-info=true` from the Psalm target; the gate must match the default
  output.
- `testing.md`: add PHPDoc to test methods (English, per the recorded exception), use `test.local`
  instead of `example.com` in test data, and add the missing success and edge-case tests.
- `symfony.md` processes: the existing array-mode `Process` usage already complies; the `--` marker
  is the only behavioral addition.
- `#[\Override]` is not applied to `ProcessFailedException::__construct`: PHP does not consider an
  internal parent constructor an override for the attribute, so it would fail at runtime and in
  static analysis.
- The narrowed `YtDlpExtension` return type on `YtDlpBundle::getContainerExtension()` is kept: it is
  part of the public signature, while the framework convention would only guarantee `?Extension`.

## Tooling levels

PHPStan stays at level 6 and Psalm at `errorLevel=4` because the rulebook sets no level and the
existing baseline is green at those levels. `phpVersion=8.4` is the minimum supported version;
`findUnusedCode=false` avoids false positives on the bundle's public API; taint analysis is off
because the only external boundary is the trusted CLI command. Static analysis covers `src` and
`config`; tests are covered by PHPUnit, 100% line coverage, and mutation checks.

## Risks / Trade-offs

- **The `--` marker changes the observable command array.** Callers that assert on
  `ProcessResult->command` will see one extra token. → Document it as a security fix in
  `CHANGELOG.md`; legitimate URL handling is unchanged.
- **Symfony 8.0 is declared but not permanently tested.** → Verify it once during implementation and
  rely on the 8.1 highest cell; revisit when 8.2 reaches Packagist.
- **Untracking the lock reduces local reproducibility.** → The library contract is the constraint
  range, not the lock; CI's lowest/highest cells are the reproducibility gate.
- **php-cs-fixer cannot parse PHP 8.6 syntax.** → CI runs lint on PHP 8.5; the Makefile passes
  `--allow-unsupported-php-version=yes` so `make lint` also works on a local PHP 8.6 host, while the
  code itself remains PHP 8.4 syntax.
- **Psalm 7 enables taint analysis by default.** → Set `runTaintAnalysis="false"` for this small
  library to keep the gate deterministic.
- **The `final readonly class` change is observable through reflection.** → Accepted; the classes are
  already `final` and their properties already `readonly`.
- **The fake binary is a bash script.** → The suite is Linux/macOS-only; CI is Ubuntu. A Windows
  contributor would need a different fake or the deferred process seam.

## Migration Plan

Release `1.1.0`. Consumers on `^1.0.4` receive it on their next `composer update`; no configuration or
code change is required. Rollback is a revert of the release tag; there is no data or schema to
migrate. The security fix is called out in the changelog and the README security note.

## Verification

- Local highest dependencies (PHP 8.6, Symfony 8.1.11 / process 8.1.10): `composer validate --strict`,
  `composer audit`, `make lint`, and `make test` all pass; the suite has 34 tests and 80 assertions.
- Local lowest dependencies (PHP 8.6, Symfony `config`/`http-kernel` 7.4.23 and
  `process`/`dependency-injection` 7.4.5): the same 34 tests and 80 assertions pass; `composer audit`
  is clean.
- Coverage: 100% of classes (8/8), methods (26/26), and lines (158/158); CI has a dedicated coverage
  job that fails below 100% line coverage.
- Mutation checks: removing the `--` marker block and forcing boolean options to be emitted both make
  the suite fail, so the tests guard the two security/option branches.
- Static analysis covers `src` and `config`; `make lint` passes on the local PHP 8.6 host through the
  Makefile's `--allow-unsupported-php-version=yes`.
- Symfony 8.0 one-off: in a temporary copy with advisory blocking disabled and only PHPUnit as a
  development dependency, the full suite passed on PHP 8.6 against `symfony/process` 8.0.13 and
  `symfony/config`, `symfony/dependency-injection`, `symfony/http-kernel` 8.0.15 (34 tests, 80
  assertions). The expected security advisories are why 8.0 is not a permanent CI cell.
- Consumer contract: `YoutubeDlProbeTest` and `DownloadSizeEstimatorTest` from
  `php-symfony-ytdownloader` passed (11 tests, 21 assertions) using this branch's
  `YtDlpClientInterface` through a temporary bootstrap.

## Open Questions

None. Symfony 8.2 is not yet on Packagist; when it is published, `^8.0` will resolve it and the
highest CI cell will exercise it without a design change.
