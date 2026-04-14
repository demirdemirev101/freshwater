<?php

namespace App\Services\Econt;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EcontCityResolverService
{
    public function __construct(
        private EcontService $econtService
    ) {}

    /**
     * Attempts to resolve an Econt city ID based on the provided city name and optional post code.
     * The resolution process prioritizes:
     * 1 Exact name + post code match
     * 2 Exact name + region name match
     * 3 Exact name + courier-enabled match
     * If no unique match is found, null is returned. Results are cached for 30 days to optimize performance.
     * @param string $cityName The name of the city to resolve.
     * @param string|null $postCode Optional post code to assist in resolution.
     * @return int|null The resolved city ID or null if resolution fails.
     * @throws \Exception If the Econt API call fails or returns an unexpected response.
     */
    public function getCityId(string $cityName, ?string $postCode = null): ?int
    {
        $normalizedName = mb_strtolower(trim($cityName));
        $normalizedPostCode = $postCode ? trim($postCode) : null;

        // Generate a cache key based on the normalized city name and post code
        $cacheKey = 'econt_city_' . md5($normalizedName . '_' . $normalizedPostCode);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($cityName, $normalizedName, $normalizedPostCode) {
            $cities = $this->econtService->getCities($cityName);

            Log::info('🔍 Resolving Econt city', [
                'city' => $cityName,
                'postCode' => $normalizedPostCode,
                'count' => count($cities),
            ]);

            /**
             * 1️⃣ Exact name + postCode (TOP PRIORITY)
             */
            if ($normalizedPostCode) {
                foreach ($cities as $city) {
                    if (
                        mb_strtolower($city['name']) === $normalizedName &&
                        isset($city['postCode']) &&
                        $city['postCode'] === $normalizedPostCode
                    ) {
                        Log::info('✅ City resolved by name + postCode', [
                            'city' => $city['name'],
                            'postCode' => $city['postCode'],
                            'id' => $city['id'],
                        ]);
                        return (int) $city['id'];
                    }
                }
            }

            /**
             * 2️⃣ Exact name + regionName
             */
            foreach ($cities as $city) {
                if (
                    mb_strtolower($city['name']) === $normalizedName &&
                    !empty($city['regionName'])
                ) {
                    Log::info('⚠️ City resolved by region', [
                        'city' => $city['name'],
                        'region' => $city['regionName'],
                        'id' => $city['id'],
                    ]);
                    return (int) $city['id'];
                }
            }

            /**
             * 3️⃣ Exact name + courier-enabled
             */
            foreach ($cities as $city) {
                if (
                    mb_strtolower($city['name']) === $normalizedName &&
                    ($city['expressCityDeliveries'] ?? false)
                ) {
                    Log::warning('⚠️ City resolved by courier availability', [
                        'city' => $city['name'],
                        'id' => $city['id'],
                    ]);
                    return (int) $city['id'];
                }
            }

            Log::error('❌ Econt city could not be uniquely resolved', [
                'searched' => $cityName,
                'postCode' => $normalizedPostCode,
            ]);

            return null;
        });
    }

    /**
     * Retrieves a list of Econt offices for a given city name. The method first resolves the city ID using the getCityId method.
     * If the city ID cannot be resolved, an empty array is returned. Otherwise,
     *  the offices are fetched from the Econt API and cached for 7 days to optimize performance.
     *  Any errors during the API call are logged and an empty array is returned in case of failure.
     * @param string $cityName The name of the city for which to retrieve offices.
     * @return array An array of offices associated with the resolved city ID, or an empty array if the city cannot be resolved or if the API call fails.
     */
    public function getOffices(string $cityName): array
    {
        $cityId = $this->getCityId($cityName);

        if (!$cityId) {
            return [];
        }

        $cacheKey = "econt_offices_{$cityId}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($cityId) {
            try {
                return $this->econtService->getOffices($cityId);
            } catch (\Throwable $e) {
                Log::error('❌ Econt offices lookup failed', [
                    'city_id' => $cityId,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }
}
