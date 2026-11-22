# Proposal

## Why

The bundle is used by production projects, but its dependency constraints stop at Symfony 7.4 and its
development tooling cannot run on current PHP: Psalm 6 does not install on PHP 8.6, and the CI matrix
stops at PHP 8.5. At the same time, user-supplied URLs are appended to the yt-dlp argv without an
end-of-options marker, so a URL-like token such as `--exec=...` can be parsed by yt-dlp as a command
option instead of a URL.

## What Changes

- Widen the four Symfony runtime constraints to `^7.4 || ^8.0` for `process`, `config`,
  `dependency-injection`, and `http-kernel`; keep `php: ^8.4` (it already covers 8.4, 8.5, and 8.6).
- Raise the development tool floor so the suite installs and runs on PHP 8.6: Psalm 7, PHPStan 2.3,
  peck 0.3, and PHPUnit 11.5.58; stop tracking the library `composer.lock`; drive CI with
  `composer update` over a PHP 8.4/8.5/8.6 x lowest/highest matrix.
- Insert the `--` end-of-options marker before URLs in the generated command, without duplicating an
  existing trailing `--`; continue to treat option keys and extra arguments as trusted input.
- Bring the internal implementation and tooling configuration in line with the project rulebook
  (`$TASKS_DIR/_code-style`) and add the missing test coverage, without changing the public API,
  configuration keys, defaults, or behavior for valid input.
- Add OpenSpec, `CONTEXT.md`, `CHANGELOG.md`, and updated README/description metadata.

Out of scope: behavior changes deferred to 2.0 — earlier empty-URL validation, wrapping Symfony
process timeouts in a `YtDlpException`, signal-derived exit codes, deduplicating `--no-warnings`,
removing the flag from `rawJson()`, stricter configuration typing, a process-factory seam, and
replacing the custom PATH scan with `ExecutableFinder`. Relative explicit binary paths combined with
a different `working_directory`, and wrapping non-exit process failures other than timeouts, are also
deferred. Also out of scope: application-only
capabilities (Doctrine, controllers, Twig, console commands) and a permanent CI cell for Symfony 8.0,
which is end-of-life and blocked by Composer's security advisories.

Backward compatibility: the change is additive for valid inputs. Public class and method signatures,
configuration keys and defaults, option semantics, and the behavior of `run()`, `download()`,
`rawJson()`, and `extractInfo()` stay unchanged. The only observable behavior change is the `--`
separator, which affects malicious or ambiguous URL tokens; it is recorded in the changelog as a
security fix.

## Capabilities

### New Capabilities

- `yt-dlp-client`: the observable contract of the wrapper — command construction, process execution
  and result interpretation, the error model, Symfony bundle configuration and dependency injection,
  and the supported runtime matrix.

### Modified Capabilities

- None. This repository has no existing capability specs; the new capability captures the current
  contract plus the security fix.

## Impact

- **Code**: `src/` (internal style and structure), `tests/`, `config/services.php`, `composer.json`,
  `.github/workflows/tests.yml`, `Makefile`, static-analysis and lint configurations.
- **Dependencies**: Symfony 7.4 and 8.x; Psalm 7; PHPStan 2.3; peck 0.3; PHPUnit 11.5.58.
- **Public API**: unchanged.
- **Docs**: `README.md`, `composer.json` description, new `CONTEXT.md` and `CHANGELOG.md`, new
  `openspec/` tree, and the session journal entry in `$TASKS_DIR/ytdownloader/_local.md` (section
  `2026-11-22 — рефакторинг php-ytdlp-wrapper`).
- **CI**: six test cells plus separate static-analysis and lint jobs.
