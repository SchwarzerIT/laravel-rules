# Changelog

All notable changes to `laravel-rules` will be documented in this file

## 1.3.0 - 2026-02-08

### Added
- Support for Laravel 11.x and 12.x
- Support for PHP 8.3 and 8.4
- Modern `ValidationRule` interface implementation
- `__invoke()` method for enhanced string shorthand support

### Changed
- **Breaking:** Minimum PHP version is now 8.3
- **Breaking:** Minimum Laravel version is now 11.0
- Migrated from deprecated `Rule` interface to `ValidationRule`
- Updated `validate()` method signature to use `Closure $fail` parameter
- Improved service provider registration for custom validation rules
- Modernized README with comprehensive usage examples
- Updated PHPUnit configuration to version 11

### Fixed
- Compatibility issues with Laravel 11+ validation system
- PHPUnit deprecation warnings

## 1.0.0 - 2020-10-12

- initial release
  - [HIBP](https://haveibeenpwned.com/) Rule
