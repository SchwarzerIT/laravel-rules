# Laravel Rules Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/schwarzer/laravel-rules.svg?style=flat-square)](https://packagist.org/packages/schwarzer/laravel-rules)
[![Build Status](https://img.shields.io/travis/SchwarzerIT/laravel-rules/master.svg?style=flat-square)](https://travis-ci.com/github/SchwarzerIT/laravel-rules)
[![Code Coverage](https://img.shields.io/coveralls/github/SchwarzerIT/laravel-rules?style=flat-square)](https://coveralls.io/github/SchwarzerIT/laravel-rules)
[![Total Downloads](https://img.shields.io/packagist/dt/schwarzer/laravel-rules.svg?style=flat-square)](https://packagist.org/packages/schwarzer/laravel-rules)
[![License](https://img.shields.io/github/license/SchwarzerIT/laravel-rules?style=flat-square)](https://github.com/SchwarzerIT/laravel-rules/blob/master/LICENSE.md)

A collection of useful Laravel validation rules to enhance your application's security and data validation.

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require schwarzer/laravel-rules
```

The service provider will be automatically registered via Laravel's package discovery.

## Available Rules

### Have I Been Pwned Password Validation

The `HaveIBeenPwned` rule checks passwords against the [Have I Been Pwned API](https://haveibeenpwned.com/API/v3) to ensure users don't use compromised passwords. This rule uses k-anonymity, so your actual password is never sent to the API - only the first 5 characters of its SHA-1 hash.

**Features:**
- Secure k-anonymity implementation
- Automatic caching to reduce API calls
- Configurable threshold for password occurrences
- Graceful error handling (fails open to not block users during API issues)

#### Setup

Add the following translation to your language files at `/resources/lang/{lang}/validation.php`:

**English:**
```php
'hibp' => 'The :attribute has been found in a data breach and cannot be used.',
```

**German (Deutsch):**
```php
'hibp' => 'Das :attribute wurde in einem Datenleck gefunden und kann nicht verwendet werden.',
```

#### Usage Examples

**Basic usage with string syntax:**
```php
use Illuminate\Support\Facades\Validator;

$validator = Validator::make($request->all(), [
    'password' => 'required|string|min:8|hibp',
]);
```

**With custom threshold (minimum occurrences):**
```php
// Only reject if password appears at least 10 times in breaches
$validator = Validator::make($request->all(), [
    'password' => 'required|string|min:8|hibp:min=10',
]);
```

**Using class syntax:**
```php
use Schwarzer\Laravel\Rules\HaveIBeenPwned;

$validator = Validator::make($request->all(), [
    'password' => ['required', 'string', 'min:8', new HaveIBeenPwned()],
]);
```

**Class syntax with custom threshold:**
```php
use Schwarzer\Laravel\Rules\HaveIBeenPwned;

$validator = Validator::make($request->all(), [
    'password' => ['required', 'string', 'min:8', new HaveIBeenPwned(10)],
]);
```

**In Form Request:**
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Schwarzer\Laravel\Rules\HaveIBeenPwned;

class RegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users',
            'password' => ['required', 'string', 'min:12', 'confirmed', new HaveIBeenPwned()],
        ];
    }
}
```

**Real-world example with registration:**
```php
namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegistrationRequest $request)
    {
        // Validation passed, including HIBP check
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Registration successful']);
    }
}
```

#### How It Works

1. The password is hashed using SHA-1
2. Only the first 5 characters of the hash are sent to the HIBP API
3. The API returns all hash suffixes matching those first 5 characters
4. The rule checks if the full password hash appears in the results
5. Results are cached for one week to minimize API calls
6. If the API is unavailable, validation passes (fail-open strategy)

#### Credits

This rule is inspired by [valorin/pwned-validator](https://github.com/valorin/pwned-validator).

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email andre@schwarzer.it instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
