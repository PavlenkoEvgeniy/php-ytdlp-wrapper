# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-11-22

### Added

- Support for Symfony 8.x alongside Symfony 7.4.
- Support and CI coverage for PHP 8.4, 8.5, and 8.6.
- `CONTEXT.md`, `CHANGELOG.md`, and OpenSpec planning artifacts.
- Test coverage for successful downloads, successful metadata extraction, option normalization,
  boolean and null option values, empty URL lists, non-executable binaries, configuration validation,
  service wiring, and the bundle extension.

### Changed

- Development tooling: Psalm 7, PHPStan 2.3, peck 0.3, PHPUnit 11.5.58.
- CI tests the lowest and highest dependencies across three PHP versions and runs dedicated
  static-analysis and lint jobs.
- `composer.lock` is no longer tracked; consumers and CI resolve dependencies from the declared
  constraints.
- Internal code follows the project rulebook: readonly value classes, `T[]` PHPDoc, aligned
  multi-call chains, and string casts at the process boundary.

### Fixed

- Scalar default arguments are cast to strings before process execution. Environment values are
  passed through unchanged, preserving Symfony Process `EnvArray` semantics (`false` omits a
  variable, `null` becomes an empty value, `Stringable` is stringified).

### Security

- The generated command now places the `--` end-of-options marker before URLs, preventing a
  URL-shaped token from being parsed by yt-dlp as a command option. Option keys and extra arguments
  remain trusted input.

[Unreleased]: https://github.com/p3s/php-ytdlp-wrapper/compare/v1.0.4...HEAD
[1.1.0]: https://github.com/p3s/php-ytdlp-wrapper/compare/v1.0.4...v1.1.0
