<?php

namespace App\Services\DataSources;

use App\Models\DataSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class InformDataClient
{
    public function __construct(
        private readonly DataSource $dataSource,
    ) {}

    /**
     * @return array{success: bool, message: string, token?: string}
     */
    public function authenticate(): array
    {
        $config = $this->dataSource->config ?? [];
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        if (blank($username) || blank($password)) {
            return [
                'success' => false,
                'message' => 'InformData username and password are required.',
            ];
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post($this->endpoint('/token'), [
                    'grant_type' => 'password',
                    'username' => $username,
                    'password' => $password,
                ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->formatHttpError('Authentication failed', $response->status(), $response->body()),
                ];
            }

            $token = $response->json('access_token');

            if (blank($token)) {
                return [
                    'success' => false,
                    'message' => 'InformData did not return an access token.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Authenticated successfully.',
                'token' => $token,
            ];
        } catch (ConnectionException $exception) {
            return [
                'success' => false,
                'message' => 'Could not reach InformData API: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        $auth = $this->authenticate();

        if (! $auth['success']) {
            return $auth;
        }

        try {
            $response = Http::withToken($auth['token'])
                ->acceptJson()
                ->timeout(20)
                ->get($this->endpoint('/api/IntegrationApi/GetCompanyProfiles'));

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->formatHttpError('Connection test failed', $response->status(), $response->body()),
                ];
            }

            return [
                'success' => true,
                'message' => 'Connected to InformData and retrieved company profiles.',
            ];
        } catch (ConnectionException $exception) {
            return [
                'success' => false,
                'message' => 'Could not reach InformData API: '.$exception->getMessage(),
            ];
        }
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->dataSource->base_url, '/').'/'.ltrim($path, '/');
    }

    private function formatHttpError(string $prefix, int $status, string $body): string
    {
        $message = trim($body);

        if ($message !== '') {
            $decoded = json_decode($message, true);
            if (is_array($decoded)) {
                $message = $decoded['error_description']
                    ?? $decoded['message']
                    ?? $decoded['error']
                    ?? $message;
            }

            $message = str($message)->limit(240)->toString();
        }

        return $message === ''
            ? "{$prefix} (HTTP {$status})."
            : "{$prefix} (HTTP {$status}): {$message}";
    }
}
