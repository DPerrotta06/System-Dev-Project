<?php

declare(strict_types=1);

namespace App\Controllers;

class GoogleReviewsController
{
    private const PLACE_ID = 'ChIJEe09ItQfyUwRFeT13j7v6OI';
    private const FIELDS = 'rating,user_ratings_total,reviews';
    private const CACHE_DURATION = 3600; // 1 hour cache

    public function getReviews(): array
    {
        // Check cache first
        $cacheFile = __DIR__ . '/../../../var/google_reviews_cache.json';
        $cachedData = $this->getCachedData($cacheFile);
        if ($cachedData !== null) {
            return $cachedData;
        }
        // Fetch from Google Places API
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/place/details/json?place_id=%s&fields=%s&key=%s',
            urlencode(self::PLACE_ID),
            self::FIELDS,
            $this->getApiKey()
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpStatusCode !== 200 || $response === false) {
            return [
                'status' => 'ERROR',
                'error_message' => 'Failed to fetch reviews from Google'
            ];
        }
        $data = json_decode($response, true);
        // Cache successful responses
        if (isset($data['status']) && $data['status'] === 'OK') {
            $this->cacheData($cacheFile, $data);
        }
        return $data;
    }

    private function getCachedData(string $cacheFile): ?array
    {
        if (!file_exists($cacheFile)) {
            return null;
        }
        $age = time() - filemtime($cacheFile);
        if ($age > self::CACHE_DURATION) {
            return null;
        }
        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    private function cacheData(string $cacheFile, array $data): void
    {
        file_put_contents($cacheFile, json_encode($data));
    }

    private function getApiKey(): string
    {
        // Get API key from environment variable
        return getenv('GOOGLE_PLACES_API_KEY') ?: '';
    }
}
