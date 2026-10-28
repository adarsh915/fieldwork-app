<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebasePushService
{
    /**
     * Send push notification to a specific user.
     */
    public static function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        if (empty($user->fcm_token)) {
            return [
                'success' => false,
                'message' => "User {$user->name} does not have a registered device push token.",
            ];
        }

        return static::sendToToken($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send push notification to multiple users.
     */
    public static function sendToUsers($users, string $title, string $body, array $data = []): array
    {
        $sentCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($users as $user) {
            if (empty($user->fcm_token)) {
                $failedCount++;
                continue;
            }

            $res = static::sendToToken($user->fcm_token, $title, $body, $data);
            if ($res['success']) {
                $sentCount++;
            } else {
                $failedCount++;
                $errors[] = "{$user->name}: " . ($res['message'] ?? 'Unknown error');
            }
        }

        return [
            'success'     => $sentCount > 0,
            'sent_count'  => $sentCount,
            'failed_count'=> $failedCount,
            'errors'      => $errors,
        ];
    }

    /**
     * Dispatch notification to a token (supports both Expo push tokens and native FCM tokens).
     */
    public static function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        // 1. If it's an Expo Push Token (ExponentPushToken[...])
        if (str_starts_with($token, 'ExponentPushToken') || str_starts_with($token, 'ExpoPushToken')) {
            return static::sendViaExpo($token, $title, $body, $data);
        }

        // 2. Direct Firebase Cloud Messaging (FCM v1 API)
        return static::sendViaFcmV1($token, $title, $body, $data);
    }

    /**
     * Send notification using Expo Push API
     */
    protected static function sendViaExpo(string $token, string $title, string $body, array $data = []): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://exp.host/--/api/v2/push/send', [
                    'to'        => $token,
                    'title'     => $title,
                    'body'      => $body,
                    'sound'     => 'default',
                    'priority'  => 'high',
                    'channelId' => 'default',
                    'data'      => $data,
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $status = $json['data']['status'] ?? 'ok';
                if ($status === 'ok') {
                    return ['success' => true, 'provider' => 'expo', 'response' => $json];
                }
                return ['success' => false, 'message' => $json['data']['message'] ?? 'Expo push rejected'];
            }

            return ['success' => false, 'message' => 'Expo push failed with HTTP ' . $response->status()];
        } catch (\Throwable $e) {
            Log::error('Expo Push error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Broadcast notification to a Firebase Topic in 1 single API call.
     */
    public static function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        $cleanTopic = ltrim($topic, '/topics/');
        return static::sendViaFcmV1Target(['topic' => $cleanTopic], $title, $body, $data);
    }

    /**
     * Subscribe device token(s) to a Firebase Topic.
     *
     * @param string|array $tokens Single FCM token or array of tokens
     * @param string $topic Topic name without '/topics/' prefix (e.g. 'all_employees')
     * @return array
     */
    public static function subscribeToTopic(string|array $tokens, string $topic = 'all_employees'): array
    {
        $tokenList = is_array($tokens) ? $tokens : [$tokens];
        $fcmTokens = array_values(array_filter($tokenList, function ($t) {
            return !empty($t) && !str_starts_with($t, 'ExponentPushToken') && !str_starts_with($t, 'ExpoPushToken');
        }));

        if (empty($fcmTokens)) {
            return ['success' => true, 'message' => 'No native FCM tokens to subscribe.'];
        }

        try {
            $credentialsPath = static::getCredentialsPath();
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                return ['success' => false, 'message' => 'Firebase credentials file not found.'];
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);
            $accessToken = static::getGoogleOAuth2Token($credentials);
            if (!$accessToken) {
                return ['success' => false, 'message' => 'Failed to generate Google OAuth2 token.'];
            }

            $cleanTopic = ltrim($topic, '/topics/');
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->withHeaders([
                    'access_token_auth' => 'true',
                    'Content-Type'      => 'application/json',
                ])
                ->post('https://iid.googleapis.com/iid/v1:batchAdd', [
                    'to'                  => '/topics/' . $cleanTopic,
                    'registration_tokens' => $fcmTokens,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'results' => $response->json()];
            }

            Log::error('FCM topic subscription error', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'message' => 'FCM subscription failed: ' . $response->body()];
        } catch (\Throwable $e) {
            Log::error('FCM topic subscription exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unsubscribe device token(s) from a Firebase Topic.
     */
    public static function unsubscribeFromTopic(string|array $tokens, string $topic = 'all_employees'): array
    {
        $tokenList = is_array($tokens) ? $tokens : [$tokens];
        $fcmTokens = array_values(array_filter($tokenList, function ($t) {
            return !empty($t) && !str_starts_with($t, 'ExponentPushToken') && !str_starts_with($t, 'ExpoPushToken');
        }));

        if (empty($fcmTokens)) {
            return ['success' => true, 'message' => 'No native FCM tokens to unsubscribe.'];
        }

        try {
            $credentialsPath = static::getCredentialsPath();
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                return ['success' => false, 'message' => 'Firebase credentials file not found.'];
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);
            $accessToken = static::getGoogleOAuth2Token($credentials);
            if (!$accessToken) {
                return ['success' => false, 'message' => 'Failed to generate Google OAuth2 token.'];
            }

            $cleanTopic = ltrim($topic, '/topics/');
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->withHeaders([
                    'access_token_auth' => 'true',
                    'Content-Type'      => 'application/json',
                ])
                ->post('https://iid.googleapis.com/iid/v1:batchRemove', [
                    'to'                  => '/topics/' . $cleanTopic,
                    'registration_tokens' => $fcmTokens,
                ]);

            return ['success' => $response->successful(), 'results' => $response->json()];
        } catch (\Throwable $e) {
            Log::error('FCM topic unsubscribe exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send notification to multiple Expo push tokens in one single batch HTTP request (up to 100).
     */
    public static function sendViaExpoBatch(array $tokens, string $title, string $body, array $data = []): array
    {
        $expoTokens = array_values(array_filter($tokens, function ($t) {
            return !empty($t) && (str_starts_with($t, 'ExponentPushToken') || str_starts_with($t, 'ExpoPushToken'));
        }));

        if (empty($expoTokens)) {
            return ['success' => true, 'sent_count' => 0];
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://exp.host/--/api/v2/push/send', [
                    'to'        => $expoTokens,
                    'title'     => $title,
                    'body'      => $body,
                    'sound'     => 'default',
                    'priority'  => 'high',
                    'channelId' => 'default',
                    'data'      => $data,
                ]);

            return ['success' => $response->successful(), 'response' => $response->json()];
        } catch (\Throwable $e) {
            Log::error('Expo Batch Push error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send notification using Firebase Cloud Messaging HTTP v1 API
     */
    protected static function sendViaFcmV1(string $token, string $title, string $body, array $data = []): array
    {
        return static::sendViaFcmV1Target(['token' => $token], $title, $body, $data);
    }

    /**
     * Common FCM v1 dispatcher supporting both individual 'token' and broadcast 'topic'.
     */
    protected static function sendViaFcmV1Target(array $target, string $title, string $body, array $data = []): array
    {
        try {
            $credentialsPath = static::getCredentialsPath();
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                return ['success' => false, 'message' => 'Firebase credentials file not found.'];
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);
            $projectId = $credentials['project_id'] ?? null;
            if (!$projectId) {
                return ['success' => false, 'message' => 'Invalid project_id in Firebase credentials.'];
            }

            $accessToken = static::getGoogleOAuth2Token($credentials);
            if (!$accessToken) {
                return ['success' => false, 'message' => 'Failed to generate Google OAuth2 token for Firebase.'];
            }

            // Stringify any non-string data values as required by FCM v1
            $formattedData = [];
            foreach ($data as $k => $v) {
                $formattedData[(string)$k] = is_string($v) ? $v : json_encode($v);
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $messagePayload = array_merge($target, [
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'default',
                    ],
                ],
            ]);

            if (!empty($formattedData)) {
                $messagePayload['data'] = $formattedData;
            }

            $payload = ['message' => $messagePayload];

            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                return ['success' => true, 'provider' => 'fcm_v1', 'response' => $response->json()];
            }

            Log::error('FCM v1 Push error response', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'message' => 'FCM error: ' . ($response->json()['error']['message'] ?? $response->body())];

        } catch (\Throwable $e) {
            Log::error('FCM Push exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get path to firebase credentials json file
     */
    protected static function getCredentialsPath(): ?string
    {
        $primary = storage_path('app/firebase_credentials.json');
        if (file_exists($primary)) return $primary;

        $files = glob(storage_path('app/*firebase*.json'));
        return !empty($files) ? $files[0] : null;
    }

    /**
     * Generate Google OAuth2 Access Token from Service Account private key using native OpenSSL.
     */
    protected static function getGoogleOAuth2Token(array $credentials): ?string
    {
        try {
            $now = time();
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claimSet = [
                'iss'   => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ];

            $encodedHeader = static::base64UrlEncode(json_encode($header));
            $encodedClaimSet = static::base64UrlEncode(json_encode($claimSet));
            $signatureInput = "{$encodedHeader}.{$encodedClaimSet}";

            $privateKey = openssl_pkey_get_private($credentials['private_key']);
            if (!$privateKey) {
                Log::error('Invalid private key in Firebase credentials');
                return null;
            }

            $signature = '';
            if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                Log::error('Failed to sign JWT for Google OAuth2');
                return null;
            }

            $jwt = "{$signatureInput}." . static::base64UrlEncode($signature);

            $tokenResponse = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($tokenResponse->successful()) {
                return $tokenResponse->json()['access_token'] ?? null;
            }

            Log::error('Google OAuth2 token exchange failed', ['body' => $tokenResponse->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('OAuth2 token generation exception: ' . $e->getMessage());
            return null;
        }
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
