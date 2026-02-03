<?php

namespace App\Services;

use App\Exceptions\DriveException;
use App\Models\User;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    private ?Google_Client $client = null;
    private ?Google_Service_Drive $service = null;

    public function __construct()
    {
        if (config('services.google.client_id') && config('services.google.client_secret')) {
            $this->client = new Google_Client();
            $this->client->setClientId(config('services.google.client_id'));
            $this->client->setClientSecret(config('services.google.client_secret'));
        }
    }

    /**
     * Check if Drive integration is available.
     */
    public function isAvailable(): bool
    {
        return $this->client !== null;
    }

    /**
     * Set user tokens for API calls.
     */
    public function setUserTokens(User $user): void
    {
        if (!$this->client) {
            throw new DriveException('Google Drive integration is not configured.');
        }

        if (!$user->google_access_token) {
            throw new DriveException('User has not connected Google Drive.');
        }

        // Check if token needs refresh
        if ($user->isGoogleTokenExpired()) {
            $this->refreshToken($user);
        }

        $this->client->setAccessToken($user->google_access_token);
        $this->service = new Google_Service_Drive($this->client);
    }

    /**
     * Refresh the user's Google access token.
     */
    private function refreshToken(User $user): void
    {
        if (!$user->google_refresh_token) {
            throw new DriveException('No refresh token available. User needs to re-authenticate.');
        }

        try {
            $this->client->setAccessType('offline');
            $this->client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

            $token = $this->client->getAccessToken();

            $user->update([
                'google_access_token' => $token['access_token'],
                'google_token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
            ]);

            Log::info('Google token refreshed', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google token', ['error' => $e->getMessage()]);
            throw new DriveException('Failed to refresh Google token: ' . $e->getMessage());
        }
    }

    /**
     * Get tokens for Google Picker.
     */
    public function getPickerToken(User $user): array
    {
        $this->setUserTokens($user);

        return [
            'access_token' => $user->google_access_token,
            'developer_key' => config('services.google.picker_api_key'),
        ];
    }

    /**
     * Download a file from Google Drive.
     * 
     * @throws DriveException
     */
    public function downloadFile(User $user, string $fileId): DownloadedFile
    {
        $this->setUserTokens($user);

        try {
            $file = $this->service->files->get($fileId, ['fields' => 'id,name,mimeType,size']);

            // Check size limit (50MB)
            if ($file->getSize() > 50 * 1024 * 1024) {
                throw new DriveException('File exceeds 50MB limit.');
            }

            // Handle Google Workspace files (need export)
            $content = match ($file->getMimeType()) {
                'application/vnd.google-apps.document' =>
                    $this->service->files->export($fileId, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', ['alt' => 'media'])->getBody()->getContents(),
                'application/vnd.google-apps.spreadsheet' =>
                    $this->service->files->export($fileId, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', ['alt' => 'media'])->getBody()->getContents(),
                default =>
                    $this->service->files->get($fileId, ['alt' => 'media'])->getBody()->getContents(),
            };

            return new DownloadedFile(
                filename: $file->getName(),
                content: $content,
                mimeType: $file->getMimeType(),
                size: strlen($content),
            );
        } catch (\Google_Service_Exception $e) {
            Log::error('Google Drive download failed', ['error' => $e->getMessage()]);
            throw new DriveException('Failed to download file from Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * List files in the user's Drive.
     */
    public function listFiles(User $user, string $query = '', int $limit = 20): array
    {
        $this->setUserTokens($user);

        try {
            $optParams = [
                'pageSize' => $limit,
                'fields' => 'files(id, name, mimeType, modifiedTime, size)',
                'orderBy' => 'modifiedTime desc',
            ];

            if ($query) {
                $optParams['q'] = "name contains '{$query}' and trashed = false";
            } else {
                $optParams['q'] = 'trashed = false';
            }

            $results = $this->service->files->listFiles($optParams);

            return array_map(fn ($file) => [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'modified_time' => $file->getModifiedTime(),
                'size' => $file->getSize(),
            ], $results->getFiles());
        } catch (\Google_Service_Exception $e) {
            Log::error('Google Drive list failed', ['error' => $e->getMessage()]);
            throw new DriveException('Failed to list files: ' . $e->getMessage());
        }
    }
}

/**
 * Value object for downloaded file.
 */
class DownloadedFile
{
    public function __construct(
        public readonly string $filename,
        public readonly string $content,
        public readonly string $mimeType,
        public readonly int $size,
    ) {}
}
