<?php

namespace App\Utilities;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
use Exception;

class ConnectionChecker
{
    /**
     * Check if the database connection is available
     * 
     * @return bool
     */
    public static function isDatabaseConnected(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (PDOException $e) {
            Log::error('Database connection failed: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error('Unknown database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if the application has internet connectivity
     * 
     * @param string $host Host to test connection with
     * @param int $port Port to use
     * @param int $timeout Timeout in seconds
     * @return bool
     */
    public static function hasInternetConnectivity(string $host = 'www.google.com', int $port = 80, int $timeout = 2): bool
    {
        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if ($connection) {
                fclose($connection);
                return true;
            }
            return false;
        } catch (Exception $e) {
            Log::error('Internet connectivity check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if an external API is reachable
     * 
     * @param string $url URL to check
     * @param int $timeout Timeout in seconds
     * @return bool
     */
    public static function isApiReachable(string $url, int $timeout = 5): bool
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode >= 200 && $httpCode < 300;
        } catch (Exception $e) {
            Log::error('API check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run comprehensive connectivity checks
     * 
     * @return array
     */
    public static function runDiagnostics(): array
    {
        return [
            'database' => self::isDatabaseConnected(),
            'internet' => self::hasInternetConnectivity(),
            'api_endpoints' => [
                'main_api' => self::isApiReachable(config('services.main_api.url', 'https://api.example.com'))
                // Add other API endpoints as needed
            ]
        ];
    }
} 