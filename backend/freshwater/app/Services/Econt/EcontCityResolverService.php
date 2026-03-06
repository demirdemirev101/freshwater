<?php

namespace App\Services\Econt;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EcontCityResolverService
{
    public function __construct(
        private EcontService $econtService
    ) {}

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
