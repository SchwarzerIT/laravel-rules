# Laravel Rules Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/schwarzer/laravel-rules.svg?style=flat-square)](https://packagist.org/packages/schwarzer/laravel-rules)
[![Build Status](https://img.shields.io/travis/SchwarzerIT/laravel-rules/master.svg?style=flat-square)](https://travis-ci.com/github/SchwarzerIT/laravel-rules)
[![Code Coverage](https://img.shields.io/coveralls/github/SchwarzerIT/laravel-rules?style=flat-square)](https://coveralls.io/github/SchwarzerIT/laravel-rules)
[![Total Downloads](https://img.shields.io/packagist/dt/schwarzer/laravel-rules.svg?style=flat-square)](https://packagist.org/packages/schwarzer/laravel-rules)
[![License](https://img.shields.io/github/license/SchwarzerIT/laravel-rules?style=flat-square)](https://github.com/SchwarzerIT/laravel-rules/blob/master/LICENSE.md)
## Installation

You can install the package via composer:

```bash
composer require schwarzer/laravel-rules
```

## Usage

Please consider reading the [Laravel Docs](https://laravel.com/docs/8.x/validation) first.

### Have I Been Pwned
[haveibeenpwned.com API v3](https://haveibeenpwned.com/API/v3)

In the next major release you'll be able to set the API key by config. 

*This rule is inspired by valorin/pwned-validator.*

#### Translation

Please add to your `/resources/lang/{lang}/validation.php` file the following line:

##### English
```
    'hibp' => 'The :attribute occurs at least :min times in a list of known passwords.',
```

##### Deutsch
```
    'hibp' => 'Das :attribute kommt mindestens :min mal in einer Liste bekannter Passwörter vor.',
```

#### Short syntax
``` php
Validator::make($request->all(), [
    'password' => 'required|hibp',
]);
```

You can specify how often your password (hash) should be found `min`imum in the [HIBP](https://haveibeenpwned.com/) results.
``` php
Validator::make($request->all(), [
    'password' => 'required|hibp:min=1',
]);
```

#### Class / Object syntax
``` php
Validator::make($request->all(), [
    'password' => ['required', new HaveIBeenPwned],
]);
```

You can specify how often your password (hash) should be found minimum in the [HIBP](https://haveibeenpwned.com/) results.
``` php
Validator::make($request->all(), [
    'password' => ['required', new HaveIBeenPwned(1)],
]);
```

## Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
