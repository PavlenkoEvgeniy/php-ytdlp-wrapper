# Context

Ubiquitous language for the yt-dlp wrapper. Terms describe the domain, not the implementation.

## Binary

The executable `yt-dlp` program. It is either resolved by name from the configured search path or
taken from an explicit file path that must exist and be executable.

## Request

An immutable description of one yt-dlp invocation: URLs, options, flags, and extra arguments.

## Command

The ordered argument list built from a request: binary, default arguments, flags, options, extra
arguments, the `--` end-of-options marker, and URLs.

## Default argument

An argument configured on the client and added to every command before request-specific parts.

## Flag

An option that carries no value and is emitted as a single command token, for example `--no-warnings`.

## Option

A named command parameter with a value, for example `format` becoming `--format best`. An array value
repeats the option name and value for each item; `true` emits the option name alone; `false` and
`null` omit the option. Option names and extra arguments are trusted input.

## Extra argument

A raw, trusted command token appended after options and before the `--` marker.

## End-of-options marker

The `--` token placed before URLs so that URL-shaped input is treated as positional data rather than
as a command option.

## Process result

The captured outcome of a command: exit code, standard output, standard error, the command itself, and
decoded JSON lines. A result is successful when the exit code is `0`.

## JSON lines

Zero or more JSON values that decode to arrays (objects or arrays), one per line of standard output.
Lines that do not decode to a JSON array or object are ignored.

## Environment

The map of child-process variables passed to Symfony Process. `false` omits a variable, `null` makes
it empty, and `Stringable` values are stringified.

## Bundle configuration

The `yt_dlp` configuration tree: `binary_path`, `default_arguments`, `working_directory`, `timeout`,
and `environment`.
