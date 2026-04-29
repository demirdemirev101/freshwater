<?php

namespace App\Services\Econt;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EcontCityResolverService
{
    private const ALL_OFFICES_CACHE_KEY = 'econt_offices_all';

    public function __construct(
        private EcontService $econtService
    ) {}

    /**
     * Attempts to resolve an Econt city ID based on the provided city name and optional post code.
     * This method currently relies on the remote cities nomenclature, which may be too large for normal checkout lookups.
     * Keep it for explicit city-ID use cases, but do not use it in the office lookup flow.
     */
    public function getCityId(string $cityName, ?string $postCode = null): ?int
    {
        $normalizedName = $this->normalizeCityName($cityName);
        $normalizedPostCode = $postCode ? trim($postCode) : null;

        $cacheKey = 'econt_city_' . md5($normalizedName . '_' . $normalizedPostCode);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($cityName, $normalizedName, $normalizedPostCode) {
            $cities = $this->econtService->getCities($cityName);

            Log::info('Resolving Econt city', [
                'city' => $cityName,
                'postCode' => $normalizedPostCode,
                'count' => count($cities),
            ]);

            if ($normalizedPostCode) {
                foreach ($cities as $city) {
                    if (
                        mb_strtolower($city['name']) === $normalizedName &&
                        isset($city['postCode']) &&
                        $city['postCode'] === $normalizedPostCode
                    ) {
                        return (int) $city['id'];
                    }
                }
            }

            foreach ($cities as $city) {
                if (
                    mb_strtolower($city['name']) === $normalizedName &&
                    ! empty($city['regionName'])
                ) {
                    return (int) $city['id'];
                }
            }

            foreach ($cities as $city) {
                if (
                    mb_strtolower($city['name']) === $normalizedName &&
                    ($city['expressCityDeliveries'] ?? false)
                ) {
                    return (int) $city['id'];
                }
            }

            Log::error('Econt city could not be uniquely resolved', [
                'searched' => $cityName,
                'postCode' => $normalizedPostCode,
            ]);

            return null;
        });
    }

    /**
     * Retrieves a list of Econt offices for a given city name.
     * To avoid loading the oversized cities nomenclature on every checkout request,
     * this flow uses the offices nomenclature and filters it locally by city name.
     */
    public function getOffices(string $cityName): array
    {
        $normalizedCityName = $this->normalizeCityName($cityName);

        if ($normalizedCityName === '') {
            return [];
        }

        $cacheKey = 'econt_offices_city_' . md5($normalizedCityName);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($cityName, $normalizedCityName) {
            try {
                $allOffices = Cache::remember(self::ALL_OFFICES_CACHE_KEY, now()->addDay(), function () {
                    return $this->econtService->getOffices();
                });

                $offices = array_values(array_filter($allOffices, function ($office) use ($normalizedCityName) {
                    return is_array($office) && $this->officeMatchesCity($office, $normalizedCityName);
                }));

                Log::info('Econt offices resolved by city name', [
                    'city' => $cityName,
                    'count' => count($offices),
                ]);

                return $offices;
            } catch (\Throwable $e) {
                Log::error('Econt offices lookup failed', [
                    'city' => $cityName,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    private function officeMatchesCity(array $office, string $normalizedCityName): bool
    {
        $candidates = [
            $office['cityName'] ?? null,
            $office['city'] ?? null,
            data_get($office, 'address.city.name'),
            data_get($office, 'address.cityName'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            if ($this->normalizeCityName($candidate) === $normalizedCityName) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCityName(?string $cityName): string
    {
        $normalized = mb_strtolower(trim((string) $cityName));
        $normalized = preg_replace('/^(гр\\.?|град)\s+/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return $normalized;
    }
}
