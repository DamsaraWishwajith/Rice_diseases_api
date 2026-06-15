<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $serviceAccountPath;

    public function __construct()
    {
        $this->serviceAccountPath = storage_path('app/firebase/google-service-account.json');
        if (!file_exists($this->serviceAccountPath) && file_exists(storage_path('app/firebase/google-service-account.json.json'))) {
            $this->serviceAccountPath = storage_path('app/firebase/google-service-account.json.json');
        }
    }

    protected function getAccessToken()
    {
        if (!file_exists($this->serviceAccountPath)) {
            Log::error("Firebase service account file not found at: " . $this->serviceAccountPath);
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        if (!$serviceAccount) {
            Log::error("Invalid Firebase service account JSON");
            return null;
        }

        $privateKey = $serviceAccount['private_key'];
        $clientEmail = $serviceAccount['client_email'];

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $payload = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = '';
        $success = openssl_sign(
            $base64UrlHeader . "." . $base64UrlPayload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$success) {
            Log::error("Failed to sign JWT for Firebase OAuth2");
            return null;
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $response = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error("Failed to fetch Firebase access token: " . $response->body());
        return null;
    }

    protected function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    public function sendNotificationToTopic($topic, $title, $body, $data = [])
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        $projectId = $serviceAccount['project_id'];

        // Normalize topic name to match FCM pattern requirements (replace spaces and special characters with underscore)
        $normalizedTopic = preg_replace('/[^a-zA-Z0-9-_.~%]/', '_', $topic);

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $message = [
            'message' => [
                'topic' => $normalizedTopic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
            ]
        ];

        $response = Http::withoutVerifying()->withToken($accessToken)->post($url, $message);

        if ($response->successful()) {
            Log::info("FCM Notification sent to topic '{$normalizedTopic}' successfully.");
            return true;
        }

        Log::error("FCM Notification send failure: " . $response->body());
        return false;
    }
}
