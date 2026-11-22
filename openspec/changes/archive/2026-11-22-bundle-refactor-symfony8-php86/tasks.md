# Tasks

## 1. OpenSpec bootstrap

- [x] 1.1 Initialize OpenSpec with the pinned CLI and the Claude tooling: run
      `npx --yes @fission-ai/openspec@1.16.0 init . --tools claude --language en --no-animation`
      and verify `openspec context --json` reports this repository as the root
- [x] 1.2 Add the project context, artifact rules, and apply/archive guidance to
      `openspec/config.yaml` and verify `openspec context --json` still resolves the root

## 2. Package metadata and dependency floor

- [x] 2.1 Widen the four Symfony runtime constraints to `^7.4 || ^8.0`, keep `php: ^8.4`, and update
      the package description; verify with `composer validate --strict`
- [x] 2.2 Raise the development tools to Psalm `^7.16.0`, PHPStan `^2.3.7`, peck `^0.3.0`, and
      PHPUnit `^11.5.58`; verify `composer update` completes on PHP 8.6 and `composer audit` is clean
- [x] 2.3 Stop tracking `composer.lock` and add it to `.gitignore`; verify `git status` no longer
      reports it and that a fresh `composer update` succeeds
- [x] 2.4 Verify the dependency floor: run `composer update --prefer-lowest --prefer-stable` followed
      by `vendor/bin/phpunit`, then run a normal `composer update` and `vendor/bin/phpunit` again;
      both runs must be green

## 3. CI matrix and static-analysis jobs

- [x] 3.1 Replace `composer install` with a per-cell `composer update`, add the
      PHP `8.4/8.5/8.6` x `lowest/highest` matrix, and key the cache on PHP version, dependency mode,
      and `composer.json`; verify the workflow is valid YAML and the six cells are enumerated
- [x] 3.2 Add a static-analysis job on PHP 8.6 with highest dependencies (PHPStan and Psalm) and a
      lint job on PHP 8.5 (php-cs-fixer and peck); verify the same commands pass locally where the
      local PHP version permits
- [x] 3.3 Fix the tooling configuration: remove `--show-info=true` from the Psalm target, remove the
      empty `includes:` from PHPStan, add `failOnRisky`/`failOnWarning`/`failOnNotice`, add a
      `<source>` section for coverage, include `config/` in the php-cs-fixer finder, and disable Psalm
      taint analysis; verify with `make lint`

## 4. URL safety

- [x] 4.1 Insert the `--` end-of-options marker before URLs in the command builder and skip it when
      the last extra argument is already `--`; add unit tests for a URL starting with `--`, an
      existing trailing `--`, and multiple URLs, and verify with
      `vendor/bin/phpunit --filter YtDlpClientTest`
- [x] 4.2 Document in the README that option keys and extra arguments are trusted input while URLs are
      treated as positional; verify the security note is present and matches the generated command

## 5. Client refactor and behavior locks

- [x] 5.1 Convert the client, request, and result classes to `final readonly class` and remove the
      now-redundant `readonly` on promoted parameters; verify the test suite and a reflection check on
      the three classes
- [x] 5.2 Apply the rulebook fixes: move `#[\Override]` above the docblock, remove the redundant
      `@param`, use `instanceof` instead of an object-to-null comparison, replace `list<T>` with
      `T[]`, use `\sprintf` instead of concatenation, add trailing commas to multiline calls, keep
      blank lines around object construction, put `;` on its own line in chains, simplify the
      duplicate dash check, and correct the JSON-line PHPDoc type; verify with `make lint`
- [x] 5.3 Cast scalar default arguments to strings in the generated command, and pass the environment
      array to Symfony Process unchanged so its EnvArray semantics (`false` omits, `null` becomes
      empty, `Stringable` is stringified) are preserved; add tests for both boundaries
- [x] 5.4 Add command-construction tests for option-name normalization, repeated array values,
      boolean true/false, null values, and the current empty-URL behavior; verify with
      `vendor/bin/phpunit --filter YtDlpClientTest`
- [x] 5.5 Add behavior tests for a successful download, a successful metadata extraction, a
      non-executable custom binary, and JSON-line parsing that ignores non-JSON lines; verify with
      `vendor/bin/phpunit --filter YtDlpClientTest`

## 6. Bundle configuration and dependency injection

- [x] 6.1 Add configuration tests for the five defaults, a fully custom configuration, an empty
      `binary_path`, and a negative `timeout`; assert that the client service receives five arguments
      and that the public interface alias points at the client; verify with
      `vendor/bin/phpunit --filter YtDlpExtensionTest`
- [x] 6.2 Add a test for the bundle extension accessor and its caching behavior; verify with
      `vendor/bin/phpunit --filter YtDlpBundleTest`

## 7. Documentation and release metadata

- [x] 7.1 Update the README and package description to PHP 8.4-8.6 and Symfony 7.4/8.x, document the
      security behavior, and remove the Symfony-7-only wording; verify by grepping for stale version
      claims
- [x] 7.2 Create `CONTEXT.md` with the terms Binary, Request, Flag, Option, Extra argument, Command,
      Process result, and JSON lines; verify every term used in the capability spec is defined there
- [x] 7.3 Add `CHANGELOG.md` with a `1.1.0` entry covering Added, Changed, Fixed, and Security, and
      bump `APP_VERSION` to `1.1.0`; verify the version matches `composer.json`
- [x] 7.4 Add the session entry to `$TASKS_DIR/ytdownloader/_local.md` linking this OpenSpec change,
      after `git -C "$TASKS_DIR" pull --rebase`; verify the notes repository is committed and pushed

## 8. Integration checks

- [x] 8.1 Run `composer validate --strict`, `make lint`, `make test`, and `composer audit`; all must
      pass
- [x] 8.2 Run `npx --yes @fission-ai/openspec@1.16.0 validate --all --strict`; it must pass
- [x] 8.3 Verify Symfony 8.0 once in a temporary copy with advisory blocking disabled and the test
      suite green; record the result in the change and then remove the temporary copy
- [x] 8.4 Verify the production consumer contract: in a temporary copy of
      `php-symfony-ytdownloader`, point the dependency at this branch with a path repository and run
      the probe and size-estimator unit tests; they must pass without code changes

## Workflow follow-up

- Present the artifacts for review; run `/opsx:apply` only after the user approves the proposal and
  spec deltas.
- After merge: archive the change, update the task journal with the release and verification results,
  and leave tagging/release to the user.
