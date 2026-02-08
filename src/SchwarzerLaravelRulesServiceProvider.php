<?php

namespace Schwarzer\Laravel\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class SchwarzerLaravelRulesServiceProvider extends ServiceProvider
{
    public const array SHORTS = [
        HaveIBeenPwned::class => 'hibp',
    ];

    public function boot(): void
    {
        Validator::extend(self::SHORTS[HaveIBeenPwned::class], function ($attribute, $value, $parameters) {
            $rule = new HaveIBeenPwned();
            return $rule($attribute, $value, $parameters);
        });
    }
}
