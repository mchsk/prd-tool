<?php

namespace App\Services;

use App\Exceptions\StorageException;
use Illuminate\Support\Facades\Log;

class FileStorageService
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = storage_path('prds');
    }

    /**
     * Create a new PRD file.
     */
    public function createPrd(string $userId, string $prdId, string $initialContent = ''): string
    {
        $userDir = "{$this->basePath}/{$userId}";

        // Create user directory if not exists
        if (!is_dir($userDir)) {
            if (!mkdir($userDir, 0755, true)) {
                Log::error('Failed to create user directory', ['path' => $userDir]);
                throw new StorageException('Failed to create user directory');
            }
        }

        $filePath = "{$userDir}/{$prdId}.md";

        // Atomic write using temp file
        $tempPath = "{$filePath}.tmp";
        if (file_put_contents($tempPath, $initialContent, LOCK_EX) === false) {
            Log::error('Failed to write PRD file', ['path' => $tempPath]);
            throw new StorageException('Failed to write PRD file');
        }

        if (!rename($tempPath, $filePath)) {
            @unlink($tempPath);
            Log::error('Failed to finalize PRD file', ['path' => $filePath]);
            throw new StorageException('Failed to finalize PRD file');
        }

        return $filePath;
    }

    /**
     * Read PRD content.
     */
    public function readPrd(string $userId, string $prdId): string
    {
        $filePath = $this->getFilePath($userId, $prdId);

        if (!file_exists($filePath)) {
            Log::warning('PRD file not found', ['path' => $filePath]);
            return '';
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            Log::error('Failed to read PRD file', ['path' => $filePath]);
            throw new StorageException('Failed to read PRD file');
        }

        return $content;
    }

    /**
     * Write PRD content.
     */
    public function writePrd(string $userId, string $prdId, string $content): void
    {
        $filePath = $this->getFilePath($userId, $prdId);

        // Ensure directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new StorageException('Failed to create directory');
            }
        }

        // Atomic write
        $tempPath = "{$filePath}.tmp";
        if (file_put_contents($tempPath, $content, LOCK_EX) === false) {
            throw new StorageException('Failed to write PRD file');
        }

        if (!rename($tempPath, $filePath)) {
            @unlink($tempPath);
            throw new StorageException('Failed to finalize PRD file');
        }
    }

    /**
     * Delete PRD file.
     */
    public function deletePrd(string $userId, string $prdId): bool
    {
        $filePath = $this->getFilePath($userId, $prdId);

        if (!file_exists($filePath)) {
            return true; // Already deleted
        }

        return @unlink($filePath);
    }

    /**
     * Check if PRD file exists.
     */
    public function exists(string $userId, string $prdId): bool
    {
        return file_exists($this->getFilePath($userId, $prdId));
    }

    /**
     * Get the file path for a PRD.
     */
    public function getFilePath(string $userId, string $prdId): string
    {
        // Validate IDs to prevent path traversal
        if (!$this->isValidUuid($userId) || !$this->isValidUuid($prdId)) {
            throw new StorageException('Invalid user or PRD ID');
        }

        return "{$this->basePath}/{$userId}/{$prdId}.md";
    }

    /**
     * Get file size in bytes.
     */
    public function getFileSize(string $userId, string $prdId): int
    {
        $filePath = $this->getFilePath($userId, $prdId);

        if (!file_exists($filePath)) {
            return 0;
        }

        return filesize($filePath) ?: 0;
    }

    /**
     * Validate UUID format.
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
