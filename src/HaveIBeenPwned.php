<?php

namespace Schwarzer\Laravel\Rules;

use Closure;
use DomainException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Throwable;
use function abs;
use function array_filter;
use function class_basename;
use function count;
use function explode;
use function is_numeric;
use function json_encode;
use function sha1;
use function sprintf;
use function trim;
use const PHP_EOL;

/**
 * Class HaveIBeenPwned
 * @package Schwarzer\Laravel\Rules
 */
class HaveIBeenPwned implements ValidationRule
{
    /**
     * @var int
     */
    private int $minimum;

    /**
     * @var string
     */
    public string $apiEndpoint = 'https://api.pwnedpasswords.com/range/';

    /**
     * @var string
     */
    private string $attribute;

    /**
     * Create a new HaveIBeenPwned instance.
     *
     * @param float|int|string|null $minimum 1, 2.0, '3', null
     */
    public function __construct(float|int|string|null $minimum = 1)
    {
        $this->minimum = (int)$minimum;
    }

    /**
     * Determine if the validation rule passes.
     *
     * In case any exception occurs, the password should be assumed safe. Don't let you users wait for third party APIs.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->attribute = $attribute;

        try {
            [$prefix, $suffix] = $this->hashAndSplit($value);

            $apiResultCollection = $this->apiResultCollection($prefix);

            return false === $apiResultCollection->has($suffix) ||
                $apiResultCollection->get($suffix) < $this->minimum;
        } catch (Throwable $throwable) {
            return true;
        }
    }

    /**
     * Validate the given attribute and value.
     *
     * @param string $attribute The name of the attribute being validated.
     * @param mixed $value The value of the attribute being validated.
     * @param Closure $fail A callback to specify validation failure.
     * @return void No return value.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail(Lang::get('validation.hibp', ['attribute' => $attribute, 'min' => $this->minimum]));
        }
    }

    /**
     * Handle the dynamic invocation of the class.
     *
     * @param string $attribute The name of the attribute being validated.
     * @param mixed $value The value of the attribute being validated.
     * @param array $parameters Additional parameters for the validation.
     * @return bool Returns true if the validation passes, otherwise false.
     * @throws Throwable
     */
    public function __invoke(string $attribute, mixed $value, array $parameters): bool
    {
        if (!empty($parameters)) {
            $this->minimum = self::getMinimumFromParams($parameters);
        }

        return $this->passes($attribute, $value);
    }

    /**
     * Generate the validation message for the HaveIBeenPwned check.
     *
     * @return string The localized validation message.
     */
    public function message(): string
    {
        return Lang::get('validation.hibp', ['attribute' => $this->attribute, 'min' => $this->minimum]);
    }

    /**
     * Hashes the provided value using SHA-1 and splits the hash into a prefix and suffix.
     *
     * @param string|null $value The input string to be hashed. Defaults to an empty string if null.
     * @return array An array containing the prefix (first 5 characters) and suffix (remaining characters) of the hash.
     */
    private function hashAndSplit(?string $value = ''): array
    {
        $hash = Str::upper(sha1($value));

        $prefix = Str::substr($hash, 0, 5);
        $suffix = Str::substr($hash, 5);

        return [$prefix, $suffix];
    }

    /**
     * Retrieve the result from the API, using a cache mechanism to minimize requests.
     *
     * @param string $prefix A string prefix used to generate the cache key and make the API request.
     * @return string The API result, either retrieved from the cache or fetched from the API.
     */
    private function getApiResult(string $prefix): string
    {
        $cacheKey = sprintf('%s_%s', class_basename($this), $prefix);

        return Cache::get($cacheKey) ?:
            Cache::remember(
                $cacheKey,
                Carbon::now()->addWeek(),
                fn() => $this->makeRequest($prefix)
            );
    }

    /**
     * Process API result and convert it into a collection.
     *
     * @param string $prefix The prefix used to query the API.
     * @return Collection A collection where keys are extracted values and values are the respective results.
     */
    private function apiResultCollection(string $prefix): Collection
    {
        return Collection::make(explode(PHP_EOL, $this->getApiResult($prefix)))
            ->map(fn(string $hashAndResultCount) => $this->toHashAndResultCountArrayOrNull($hashAndResultCount))
            ->filter()
            ->pluck('value', 'key');
    }

    /**
     * Sends an HTTP GET request to the specified API endpoint with the given prefix.
     *
     * @param string $prefix The prefix to append to the API endpoint URL.
     * @return string The response body of the HTTP request.
     * @throws RequestException
     * @throws ConnectionException
     */
    private function makeRequest(string $prefix): string
    {
        return Http::withHeaders(['Add-Padding' => 'true'])
            ->retry(0)
            ->get($this->apiEndpoint . $prefix)
            ->throw()
            ->body();
    }

    /**
     * Converts a delimited string into an associative array containing a key and a numeric value.
     *
     * @param string $hashAndResultCount A string containing a key and a numeric value separated by a colon.
     * @return array|null An associative array with 'key' and 'value' keys if the input is valid, or null if invalid.
     */
    private function toHashAndResultCountArrayOrNull(string $hashAndResultCount): ?array
    {
        $pair = explode(':', trim($hashAndResultCount), 2);

        return 2 === count($pair) && is_numeric(Arr::last($pair))
            ? ['key' => Arr::first($pair), 'value' => Arr::last($pair)]
            : null;
    }

    /**
     * Extracts and returns the minimum value from the provided parameters.
     * The parameters array must contain exactly one argument in the format "min={value}".
     * If invalid or multiple arguments are provided, an exception is thrown.
     *
     * @param array $parameters An array containing a single parameter in the format "min={value}" (default is ['min=1']).
     * @return int The extracted minimum value. Defaults to 1 if the extracted value is 0.
     * @throws DomainException|Throwable If the parameters array is empty, contains more than one argument,
     *                         or does not follow the required "min={value}" format.
     */
    public static function getMinimumFromParams(array $parameters = ['min=1']): int
    {
        $filtered = array_filter(empty($parameters) ? ['min=1'] : $parameters);

        if (1 !== count($filtered)) {
            throw new DomainException(
                sprintf(
                    'The rule %s only accepts one argument ("min"), more were provided: %s',
                    class_basename(self::class),
                    json_encode($parameters, JSON_THROW_ON_ERROR)
                )
            );
        }

        $firstParameter = (string)Arr::first($filtered);

        if (!Str::of($firstParameter)->startsWith('min=')) {
            throw new DomainException(
                sprintf(
                    'The rule %s only accepts one argument ("min"), something different provided: %s',
                    class_basename(self::class),
                    $firstParameter
                )
            );
        }

        $minimum = abs((int)Str::after($firstParameter, 'min='));

        return 0 === $minimum ? 1 : $minimum;
    }
}
