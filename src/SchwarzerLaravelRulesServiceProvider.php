<?php

namespace Schwarzer\Laravel\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class SchwarzerLaravelRulesServiceProvider extends ServiceProvider
{
    public const SHORTS = [
        HaveIBeenPwned::class => 'hibp',
    ];

    public function boot(): void
    {
        Validator::extend(self::SHORTS[HaveIBeenPwned::class], HaveIBeenPwned::class);
    }
}
