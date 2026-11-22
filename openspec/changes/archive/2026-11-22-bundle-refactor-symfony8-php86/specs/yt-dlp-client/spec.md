# Spec Delta

## Purpose

Defines the observable contract of the yt-dlp wrapper: how a request becomes a command, how the
process result is interpreted, which errors callers can rely on, how the Symfony bundle is configured,
and which runtime combinations are supported.

## ADDED Requirements

### Requirement: Ordered command construction

Given a request, the wrapper SHALL produce one ordered command consisting of the resolved binary,
default arguments, flags, options, extra arguments, and then the URLs. It MUST execute the command
without a shell.

#### Scenario: Request parts keep their order

- **WHEN** a request contains flags, options, extra arguments, and multiple URLs
- **THEN** the command lists them in the order binary, default arguments, flags, options, extra
  arguments, URLs

#### Scenario: Array option values are repeated

- **WHEN** an option value is a list
- **THEN** the option name and value pair is emitted once for each list item

#### Scenario: Boolean and null option values

- **WHEN** an option value is `true`
- **THEN** only the option name is emitted
- **WHEN** an option value is `false` or `null`
- **THEN** the option is omitted from the command

#### Scenario: Option names are normalized

- **WHEN** an option or flag name has no leading dash
- **THEN** the command name starts with `--`
- **WHEN** an option or flag name already starts with one or two dashes
- **THEN** the name is used unchanged

#### Scenario: A request without URLs fails

- **WHEN** a request has no URLs and the binary resolves
- **THEN** a wrapper error is raised without starting a process

### Requirement: URLs are separated from options

The wrapper MUST place the `--` end-of-options marker immediately before the first URL, unless the
preceding token is already `--`, so that URL-shaped input cannot be interpreted as a command option.

#### Scenario: A URL that looks like an option is positional

- **WHEN** a URL begins with `--` or `-`
- **THEN** it appears after the `--` marker and is treated by yt-dlp as a URL, not as an option

#### Scenario: An existing separator is not duplicated

- **WHEN** the extra arguments already end with `--`
- **THEN** exactly one `--` token precedes the URLs

#### Scenario: Multiple URLs share one separator

- **WHEN** a request contains several URLs
- **THEN** all URLs follow a single `--` marker

### Requirement: Execution captures the process result

Executing a command SHALL capture the exit code, standard output, standard error, and the JSON values
that decode to arrays from standard output, without a shell.

#### Scenario: A successful process

- **WHEN** the process exits with code `0`
- **THEN** the result reports success and exposes the captured output

#### Scenario: A failed process

- **WHEN** the process exits with a non-zero code
- **THEN** the result reports failure and exposes the exit code, output, and error output

#### Scenario: JSON lines are decoded

- **WHEN** standard output contains one JSON array or object per line plus non-JSON lines
- **THEN** the result contains only the decoded JSON values, in line order

### Requirement: Wrapper-specific error model

The wrapper SHALL raise a binary-not-found error when the binary cannot be resolved, and a
process-failure error carrying the process result when a caller requires success and the process
failed.

#### Scenario: An explicit binary path is unusable

- **WHEN** the configured binary path does not exist or is not executable
- **THEN** a binary-not-found error naming the path is raised

#### Scenario: A default binary is absent from the search path

- **WHEN** the binary name is the default and it is not found in the configured search path
- **THEN** a binary-not-found error is raised

#### Scenario: Required success fails

- **WHEN** a process fails and the caller requires success
- **THEN** a process-failure error exposes the exit code, command, and output

### Requirement: Metadata operations

A metadata execution SHALL add `--dump-json`, `--skip-download`, and `--no-warnings` to the request.
The convenience metadata operation SHALL return the decoded JSON values.

#### Scenario: Metadata flags are added

- **WHEN** metadata is requested
- **THEN** the command contains `--dump-json`, `--skip-download`, and `--no-warnings`

#### Scenario: Metadata succeeds

- **WHEN** metadata execution succeeds
- **THEN** the decoded JSON values are returned in line order

#### Scenario: Metadata fails

- **WHEN** metadata execution fails
- **THEN** a process-failure error is raised

### Requirement: Bundle configuration and service wiring

The bundle SHALL expose `binary_path`, `default_arguments`, `working_directory`, `timeout`, and
`environment` with defaults `yt-dlp`, `--no-warnings`, `null`, `300.0`, and an empty map. It SHALL
register a client service and alias the public client interface to it.

#### Scenario: Defaults apply

- **WHEN** no bundle configuration is provided
- **THEN** the resolved values are `yt-dlp`, `--no-warnings`, `null`, `300.0`, and an empty map

#### Scenario: Custom configuration applies

- **WHEN** custom values are provided for all five keys
- **THEN** the client receives those values

#### Scenario: Invalid configuration is rejected

- **WHEN** `binary_path` is empty or `timeout` is negative
- **THEN** container configuration fails with a configuration error

#### Scenario: The client interface resolves

- **WHEN** the container is built
- **THEN** the public client interface resolves to the client service

### Requirement: Supported runtime matrix

The package SHALL install and pass its test suite on PHP 8.4, 8.5, and 8.6, and on Symfony 7.4 and
8.x.

#### Scenario: Lowest supported dependencies

- **WHEN** the package is installed with the lowest allowed dependencies on PHP 8.4
- **THEN** the full test suite passes

#### Scenario: Highest supported dependencies

- **WHEN** the package is installed with the highest allowed dependencies on PHP 8.6
- **THEN** the full test suite passes

#### Scenario: Symfony 8 kernel integration

- **WHEN** the bundle is registered in a Symfony 8 kernel
- **THEN** the extension loads and the client service is available

### Requirement: Backward compatibility within 1.x

Within the 1.x line, public operations, configuration keys and defaults, option semantics, and
behavior for valid input SHALL remain unchanged.

#### Scenario: Existing calls remain valid

- **WHEN** a 1.0 consumer uses the low-level, download, raw JSON, or metadata operations with valid
  input
- **THEN** the result is unchanged apart from the `--` separator required for URL safety

#### Scenario: Existing configuration remains valid

- **WHEN** an existing 1.0 configuration is loaded
- **THEN** every key and default resolves to the same value as before
