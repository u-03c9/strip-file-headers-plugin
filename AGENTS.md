# Repository Guidelines

## Project Structure & Module Organization

This repository is a small Composer plugin for stripping leading file header comments.

- `composer.json` defines package metadata, the Composer plugin entry class, PSR-4 autoloading, and the exposed binary.
- `src/` contains the plugin code under the `Project\StripFileHeaders` namespace.
- `src/ComposerPlugin.php` wires Composer `post-install-cmd` and `post-update-cmd` hooks.
- `src/HeaderStripper.php`, `src/StripperConfig.php`, and `src/StripResult.php` hold the stripping logic, configuration parsing, and result value object.
- `bin/strip-file-headers` is the manual CLI entrypoint.
- `README.md` documents installation, configuration, and user-facing examples.

There is no dedicated `tests/` directory yet.

## Build, Test, and Development Commands

- `composer install` installs development dependencies and generates `vendor/autoload.php`.
- `composer validate` checks Composer package metadata.
- `php -l src/HeaderStripper.php` checks PHP syntax for one file; repeat for changed PHP files.
- `php bin/strip-file-headers --help` verifies the CLI can bootstrap and print usage.
- `php bin/strip-file-headers --dry-run --root=/path/to/project` previews changes without modifying files.

When testing against another project, configure this package as a Composer path repository and enable `allow-plugins.u-03c9/strip-file-headers-plugin`.

## Coding Style & Naming Conventions

Use PHP 7.4-compatible syntax and keep `declare(strict_types=1);` at the top of PHP files. Follow the existing style: 4-space indentation, final classes where extension is not intended, typed properties and return types, short guard clauses, and docblocks for array shapes or list types that PHP cannot express directly.

Class names use PascalCase. Methods and variables use camelCase. Configuration keys and CLI flags use lowercase kebab-case, for example `strip-file-headers`, `--dry-run`, and `--dir=vendor`.

## Testing Guidelines

No automated test suite is currently configured. For behavior changes, run syntax checks, `composer validate`, and at least one dry-run against a fixture or real consuming project. Prefer adding future tests around `HeaderStripper::stripHeader()` and `StripperConfig` parsing before changing edge-case stripping behavior.

## Commit & Pull Request Guidelines

The current git history uses short, imperative lowercase messages such as `init`. Keep commits focused and similarly direct, for example `add exclude handling`.

Pull requests should include a concise summary, the commands run, and a before/after example when stripping behavior changes. Note any license-impacting changes clearly because this tool can rewrite third-party files under `vendor/`.

## Security & Configuration Tips

Be careful with defaults that affect `vendor/`. Removing third-party copyright or license notices can violate package licenses. Preserve `STRIP_FILE_HEADERS_DISABLE=1` as an emergency opt-out for Composer hooks.
