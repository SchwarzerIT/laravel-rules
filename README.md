# Laravel Rules Package (Deprecated)

> **This package is deprecated and no longer maintained.** Laravel's built-in [`Password`](https://laravel.com/docs/validation#validating-passwords) rule provides the same HIBP breach checking via `uncompromised()` since Laravel 8. Please migrate to the native solution.

## Migration Guide

### 1. Remove this package

```bash
composer remove schwarzer/laravel-rules
```

### 2. Replace validation rules

#### String syntax `hibp`

```php
// Before
'password' => 'required|string|min:8|hibp',

// After
use Illuminate\Validation\Rules\Password;

'password' => ['required', 'string', Password::min(8)->uncompromised()],
```

#### String syntax with threshold `hibp:min=N`

```php
// Before
'password' => 'required|string|min:8|hibp:min=5',

// After
use Illuminate\Validation\Rules\Password;

'password' => ['required', 'string', Password::min(8)->uncompromised(5)],
```

#### Class syntax `new HaveIBeenPwned()`

```php
// Before
use Schwarzer\Laravel\Rules\HaveIBeenPwned;

'password' => ['required', 'string', 'min:8', new HaveIBeenPwned()],

// After
use Illuminate\Validation\Rules\Password;

'password' => ['required', 'string', Password::min(8)->uncompromised()],
```

#### Class syntax with threshold `new HaveIBeenPwned(N)`

```php
// Before
use Schwarzer\Laravel\Rules\HaveIBeenPwned;

'password' => ['required', 'string', 'min:8', new HaveIBeenPwned(5)],

// After
use Illuminate\Validation\Rules\Password;

'password' => ['required', 'string', Password::min(8)->uncompromised(5)],
```

#### Form Request example

```php
// Before
use Schwarzer\Laravel\Rules\HaveIBeenPwned;

public function rules(): array
{
    return [
        'password' => ['required', 'string', 'min:12', 'confirmed', new HaveIBeenPwned()],
    ];
}

// After
use Illuminate\Validation\Rules\Password;

public function rules(): array
{
    return [
        'password' => ['required', 'string', 'confirmed', Password::min(12)->uncompromised()],
    ];
}
```

### 3. Remove custom translations

You can delete the `hibp` key from your `validation.php` language files. Laravel provides its own `password.uncompromised` translation out of the box.

### Threshold default difference

This package defaulted to a threshold of **1** (password must appear at least once). Laravel's `uncompromised()` defaults to **0** (any occurrence fails). If you relied on the default, the migration is slightly stricter, which is generally desirable.

## Archiving this repository

This repository is archived and read-only. No further updates will be made.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
