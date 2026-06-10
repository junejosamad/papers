<?php
/**
 * JSON Response Helper
 * Standardized API responses with caching support
 */

class ApiResponse
{

    /**
     * Send success response
     */
    public static function success($data = null, $meta = null, $statusCode = 200)
    {
        http_response_code($statusCode);

        $response = ['success' => true];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send paginated response
     */
    public static function paginated($data, $page, $perPage, $total)
    {
        $totalPages = ceil($total / $perPage);

        self::success($data, [
            'page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => (int) $total,
            'total_pages' => (int) $totalPages,
            'has_more' => $page < $totalPages
        ]);
    }

    /**
     * Send error response
     */
    public static function error($code, $message = null, $field = null, $statusCode = 400)
    {
        http_response_code($statusCode);

        $error = [
            'code' => $code,
            'message' => $message ?? (ERROR_CODES[$code] ?? 'Unknown error')
        ];

        if ($field) {
            $error['field'] = $field;
        }

        echo json_encode([
            'success' => false,
            'error' => $error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send validation errors
     */
    public static function validationError($errors)
    {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'errors' => $errors
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Set ETag header and check for 304
     */
    public static function withETag($data)
    {
        $etag = '"' . md5(json_encode($data)) . '"';
        header("ETag: $etag");

        $clientETag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($clientETag === $etag) {
            http_response_code(304);
            exit;
        }

        return $data;
    }

    /**
     * Set cache control headers
     */
    public static function setCacheHeaders($maxAge = 300)
    {
        header("Cache-Control: public, max-age=$maxAge");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }

    /**
     * No cache headers
     */
    public static function noCache()
    {
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Pragma: no-cache");
    }
}
