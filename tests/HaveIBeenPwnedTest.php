<?php

namespace Schwarzer\Laravel\Rules\Tests;

use DomainException;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;
use const PHP_INT_MAX;
use Schwarzer\Laravel\Rules\HaveIBeenPwned;
use Schwarzer\Laravel\Rules\SchwarzerLaravelRulesServiceProvider;

class HaveIBeenPwnedTest extends TestCase
{
    private Rule $rule;

    private string $passwordPassword = 'password';

    private string $passwordRandom = 'jJv1zT7c]}L>G?R7=f^WFoA5M+-cVJeU:C._MEWCLpjs6y5o6uRfk39e-qAd';

    private string $attribute = 'test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new HaveIBeenPwned;
    }

    protected function getPackageProviders($app)
    {
        return [SchwarzerLaravelRulesServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.driver', 'array');
    }

    public function testPasswordIsAnUnsafePassword()
    {
        $this->fakeRequestForPasswordPassword();

        $passwordToValidate = $this->passwordPassword;

        $this->assertFalse($this->rule->passes($this->attribute, $passwordToValidate));
    }

    public function testRuleWorksWithMinimum()
    {
        $this->fakeRequestForPasswordPassword();

        $passwordToValidate = $this->passwordPassword;

        $this->rule = new HaveIBeenPwned(1);

        $this->assertFalse($this->rule->passes($this->attribute, $passwordToValidate));

        $this->rule = new HaveIBeenPwned(PHP_INT_MAX);

        $this->assertTrue($this->rule->passes($this->attribute, $passwordToValidate));
    }

    public function testCacheWorksForRepetitiveChecks()
    {
        $this->fakeRequestForPasswordPassword();

        $passwordToValidate = $this->passwordPassword;

        $this->expectsEvents(CacheMissed::class);
        $this->assertFalse($this->rule->passes($this->attribute, $passwordToValidate));

        $this->expectsEvents(CacheHit::class);
        $this->assertFalse($this->rule->passes($this->attribute, $passwordToValidate));
    }

    public function testLongRandomCharactersAreASafePassword()
    {
        $this->fakeRequestForPasswordRandom();

        $passwordToValidate = $this->passwordRandom;

        $this->assertTrue($this->rule->passes($this->attribute, $passwordToValidate));
    }

    public function testExceptionsDontFailThePasswordCheck()
    {
        Http::fake([
            $this->rule->apiEndpoint . '*' => Http::response(null, 500, []),
        ]);

        $passwordToValidate = $this->passwordPassword;

        $this->assertTrue($this->rule->passes($this->attribute, $passwordToValidate));
    }

    public function testValidatorShortHandleWorks()
    {
        $this->fakeRequestForPasswordRandom();

        $input = [
            $this->attribute => $this->passwordRandom,
        ];

        $rules = [
            $this->attribute => 'hibp',
        ];

        $this->assertTrue(Validator::make($input, $rules)->passes());
    }

    public function testValidatorShortHandleWorksWithMinimum()
    {
        $this->fakeRequestForPasswordPassword();

        $input = [
            $this->attribute => $this->passwordPassword,
        ];

        // PHP_INT_MAX often in the result set

        $min = (string) PHP_INT_MAX;

        $rules = [
            $this->attribute => 'hibp:min=' . $min,
        ];

        $this->assertTrue(Validator::make($input, $rules)->passes());

        // at least once in the result set

        $min = (string) 1;

        $rules = [
            $this->attribute => 'hibp:min=' . $min,
        ];

        $this->assertFalse(Validator::make($input, $rules)->passes());
    }

    public function testValidatorShortHandleOnlyAcceptsMinOption()
    {
        $this->fakeRequestForPasswordPassword();

        $input = [
            $this->attribute => $this->passwordPassword,
        ];

        // PHP_INT_MAX often in the result set

        $min = (string) 1;

        $rules = [
            $this->attribute => 'hibp:min=' . $min,
        ];

        $this->assertFalse(Validator::make($input, $rules)->passes());

        // at least once in the result set

        $max = (string) 1;

        $rules = [
            $this->attribute => 'hibp:min=' . $min . ',max=' . $max,
        ];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('The rule HaveIBeenPwned only accepts one argument ("min"), more were provided: ["min=1","max=1"]');
        Validator::make($input, $rules)->passes();
    }

    public function testValidatorClassInstanceWorks()
    {
        $this->fakeRequestForPasswordRandom();

        $input = [
            $this->attribute => $this->passwordRandom,
        ];

        $rules = [
            $this->attribute => $this->rule,
        ];

        $this->assertTrue(Validator::make($input, $rules)->passes());
    }

    public function testValidationMessageIsReturned()
    {
        $this->fakeRequestForPasswordPassword();

        $input = [
            $this->attribute => $this->passwordPassword,
        ];

        $rules = [
            $this->attribute => 'hibp',
        ];

        $validated = Validator::make($input, $rules)->errors();

        $this->assertEquals('validation.hibp', $validated->first($this->attribute));

        $rules = [
            $this->attribute => $this->rule,
        ];

        $validated = Validator::make($input, $rules)->errors();

        $this->assertEquals('validation.hibp', $validated->first($this->attribute));
    }

    private function fakeRequestForPasswordPassword(): void
    {
        $response = File::get(__DIR__ . '/Fixtures/api-result-password-password.txt');

        Http::fake([
            $this->rule->apiEndpoint . '*' => Http::response($response, 200, ['content-type' => 'text/plain']),
        ]);
    }

    private function fakeRequestForPasswordRandom(): void
    {
        $response = File::get(__DIR__ . '/Fixtures/api-result-password-random.txt');

        Http::fake([
            $this->rule->apiEndpoint . '*' => Http::response($response, 200, ['content-type' => 'text/plain']),
        ]);
    }
}
