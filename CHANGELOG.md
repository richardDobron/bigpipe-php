# Changelog

All notable changes to `bigpipe-util` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## Unreleased

### Added
- `Context` holds the pagelets and jsmods of a request; `BigPipe::setContextResolver()` and `BigPipe::withContext()`
  give long-running servers (Octane, FrankenPHP, RoadRunner, Swoole) a fresh context per request.
- `BigPipe` and `AsyncResponse` accept an optional `Context` in the constructor.

### Fixed
- The state is reset even when encoding the response or rendering the pagelets throws, so it no longer leaks into
  the next request.
- PHP 8.4 deprecation of implicitly nullable parameters in `require()`.

## v1.0.5 - 2025-09-07

### Added
- Require proxy support.

## v1.0.4 - 2025-01-16

### Added
- `$limit` parameter to `closeDialogs()` method.

## v1.0.3 - 2024-11-15

### Added
- Priority parameter to `require()` method.

## v1.0.2 - 2023-02-04

### Fixed
- Catch exception during string conversion

## v1.0.1 - 2022-12-23

### Added
- Module transport method `transportModule()`

## v1.0.0 - 2022-07-27

### Changed
- Set minimum required PHP version to 8.0.
- Change return value type to static.

## v0.2.0 - 2022-05-17

### Added
- Shield to prevent "JSON Hijacking"

## v0.1.4 - 2022-05-06

### Added
- Method `generate_unique_node_id` to generate unique node ID.
- Support for array in require().
- Support arguments for dialog controller.

## v0.1.3 - 2022-04-27

### Added
- Method to close only current dialog.
- Support of __toString in require arguments.

## v0.1.2 - 2022-04-14

### Added
- Dialogs support.

## v0.1.1 - 2022-03-27

### Added
- Part of BigPipe implementation for Webpack.
