<?php

namespace Schwarzer\Laravel\Rules;

use function abs;
use function array_filter;
use function class_basename;
use function count;
use DomainException;
use function explode;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use function is_numeric;
use function json_encode;
use const PHP_EOL;
use function sha1;
use function sprintf;
use function throw_unless;
use Throwable;
use function trim;

class HaveIBeenPwned implements Rule
{
    private int $minimum;

    public string $apiEndpoint = 'https://api.pwnedpasswords.com/range/';

    private string $attribute;

    /**
     * Create a new HaveIBeenPwned instance.
     *
     * @param int|float|string|null $minimum 1, 2.0, '3', null
     */
    public function __construct($minimum = 1)
    {
        $this->minimum = (int) $minimum;
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
    public function passes($attribute, $value): bool
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

    public function validate(string $attribute, $value, array $params): bool
    {
        $this->minimum = self::getMinimumFromParams($params);

        return $this->passes($attribute, $value);
    }

    public function message(): string
    {
        return Lang::get('validation.hibp', ['attribute' => $this->attribute, 'min' => $this->minimum]);
    }

    /**
     * @param string|null $value
     *
     * @return array[prefix, suffix]
     */
    private function hashAndSplit(?string $value = ''): array
    {
        $hash = Str::upper(sha1($value));

        $prefix = Str::substr($hash, 0, 5);
        $suffix = Str::substr($hash, 5);

        return [$prefix, $suffix];
    }

    private function getApiResult(string $prefix): string
    {
        $cacheKey = sprintf('%s_%s', class_basename($this), $prefix);

        return Cache::get($cacheKey) ?:
            Cache::remember(
                $cacheKey,
                Carbon::now()->addWeek(),
                fn () => $this->makeRequest($prefix)
            );
    }

    private function apiResultCollection(string $prefix): Collection
    {
        return Collection::make(explode(PHP_EOL, $this->getApiResult($prefix)))
            ->map(fn (string $hashAndResultCount) => $this->toHashAndResultCountArrayOrNull($hashAndResultCount))
            ->filter()
            ->pluck('value', 'key');
    }

    private function makeRequest(string $prefix)
    {
        return Http::withHeaders(['Add-Padding' => 'true'])
            ->retry(0)
            ->get($this->apiEndpoint . $prefix)
            ->throw()
            ->body();
    }

    private function toHashAndResultCountArrayOrNull(string $hashAndResultCount): ?array
    {
        $pair = explode(':', trim($hashAndResultCount), 2);

        return 2 === count($pair) && is_numeric(Arr::last($pair))
            ? ['key' => Arr::first($pair), 'value' => Arr::last($pair)]
            : null;
    }

    public static function getMinimumFromParams(array $parameters = ['min=1']): int
    {
        $filtered = array_filter(empty($parameters) ? ['min=1'] : $parameters);

        throw_unless(
            1 === count($filtered),
            DomainException::class,
            sprintf('The rule %s only accepts one argument ("min"), more were provided: %s', class_basename(self::class), json_encode($parameters))
        );

        $firstParameter = (string) Arr::first($filtered);

        throw_unless(
            Str::of($firstParameter)->startsWith('min='),
            DomainException::class,
            sprintf('The rule %s only accepts one argument ("min"), something different provided: %s', class_basename(self::class), $firstParameter)
        );

        $minimum = abs((int) Str::after($firstParameter, 'min='));

        return 0 === $minimum ? 1 : $minimum;
    }
}
