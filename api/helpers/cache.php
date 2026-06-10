<?php
/**
 * Cache Helper
 * File-based caching with TTL support
 */

class ApiCache
{

    /**
     * Get cached data
     */
    public static function get($key)
    {
        $file = self::getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (!$data || !isset($data['expires_at'])) {
            return null;
        }

        // Check expiration
        if ($data['expires_at'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set cached data
     */
    public static function set($key, $value, $ttl = CACHE_TTL_SHORT)
    {
        $file = self::getFilePath($key);

        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];

        return file_put_contents($file, json_encode($data)) !== false;
    }

    /**
     * Remember - get from cache or compute
     */
    public static function remember($key, $ttl, $callback)
    {
        $cached = self::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * Delete cached data
     */
    public static function delete($key)
    {
        $file = self::getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Clear all cache
     */
    public static function clear()
    {
        $files = glob(CACHE_DIR . '*.json');

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    /**
     * Clear expired cache
     */
    public static function clearExpired()
    {
        $files = glob(CACHE_DIR . '*.json');
        $cleared = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (!$data || $data['expires_at'] < time()) {
                unlink($file);
                $cleared++;
            }
        }

        return $cleared;
    }

    /**
     * Generate cache file path
     */
    private static function getFilePath($key)
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return CACHE_DIR . $safeKey . '.json';
    }

    /**
     * Generate ETag for data
     */
    public static function generateETag($data)
    {
        return '"' . md5(json_encode($data)) . '"';
    }

    /**
     * Check If-None-Match header
     */
    public static function checkNotModified($etag)
    {
        $clientETag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

        if ($clientETag === $etag) {
            http_response_code(304);
            exit;
        }
    }
}
