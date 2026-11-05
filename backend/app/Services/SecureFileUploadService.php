<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Secure File Upload Service
 * 
 * Provides centralized, secure file upload handling with validation
 * 
 * Features:
 * - File type validation (MIME + extension)
 * - File size validation
 * - Secure filename generation
 * - Audit logging
 * - Error handling
 * 
 * Usage:
 *   $service = new SecureFileUploadService();
 *   $path = $service->upload($file, 'uploads/payments', 'document');
 */
class SecureFileUploadService
{
    /**
     * Allowed file configurations by type
     */
    private const FILE_TYPES = [
        'image' => [
            'mimes' => ['image/jpeg', 'image/png', 'image/jpg'],
            'extensions' => ['jpg', 'jpeg', 'png'],
            'max_size' => 5 * 1024 * 1024,  // 5MB
            'description' => 'Images (JPG, PNG)'
        ],
        'document' => [
            'mimes' => ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'],
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'max_size' => 10 * 1024 * 1024,  // 10MB
            'description' => 'Documents (PDF, JPG, PNG)'
        ],
        'avatar' => [
            'mimes' => ['image/jpeg', 'image/png', 'image/jpg'],
            'extensions' => ['jpg', 'jpeg', 'png'],
            'max_size' => 2 * 1024 * 1024,  // 2MB
            'description' => 'Avatar Images (JPG, PNG)'
        ],
    ];

    /**
     * Upload file securely with validation
     *
     * @param UploadedFile $file The file to upload
     * @param string $directory Storage directory (e.g., 'uploads/payments')
     * @param string $type File type: 'image', 'document', or 'avatar'
     * @return string File path relative to storage/app/public
     * @throws InvalidArgumentException If validation fails
     */
    public function upload(UploadedFile $file, string $directory, string $type = 'image'): string
    {
        // Validate type exists
        if (!isset(self::FILE_TYPES[$type])) {
            throw new InvalidArgumentException("Invalid upload type: {$type}. Allowed: image, document, avatar");
        }

        $config = self::FILE_TYPES[$type];

        // Validate file is actually uploaded
        if (!$file->isValid()) {
            throw new InvalidArgumentException('File upload failed. Please try again.');
        }

        // Validate file size
        if ($file->getSize() > $config['max_size']) {
            $maxMB = round($config['max_size'] / 1024 / 1024, 1);
            throw new InvalidArgumentException("File too large. Maximum size: {$maxMB}MB");
        }

        // Validate MIME type (server-side, cannot be faked)
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $config['mimes'], true)) {
            throw new InvalidArgumentException(
                "Invalid file type. Allowed: {$config['description']}"
            );
        }

        // Validate extension (double-check)
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $config['extensions'], true)) {
            throw new InvalidArgumentException(
                "Invalid file extension. Allowed: " . implode(', ', $config['extensions'])
            );
        }

        // Generate secure, unpredictable filename
        $fileName = $this->generateSecureFilename($extension);

        // Store file
        try {
            $path = $file->storeAs($directory, $fileName, 'public');
        } catch (\Exception $e) {
            Log::error('File storage failed', [
                'error' => $e->getMessage(),
                'directory' => $directory,
                'type' => $type
            ]);
            throw new InvalidArgumentException('Failed to store file. Please try again.');
        }

        // Audit log
        $this->logUpload($fileName, $file, $directory, $type);

        return $path;
    }

    /**
     * Generate secure, unpredictable filename
     * 
     * Uses UUID + timestamp to prevent:
     * - Filename guessing
     * - Directory traversal
     * - Overwrites
     *
     * @param string $extension File extension
     * @return string Secure filename
     */
    private function generateSecureFilename(string $extension): string
    {
        // UUID ensures uniqueness and unpredictability
        // Timestamp helps with chronological ordering
        return Str::uuid() . '_' . time() . '.' . $extension;
    }

    /**
     * Log file upload for security audit
     *
     * @param string $fileName Generated filename
     * @param UploadedFile $file Uploaded file
     * @param string $directory Storage directory
     * @param string $type File type
     * @return void
     */
    private function logUpload(string $fileName, UploadedFile $file, string $directory, string $type): void
    {
        Log::info('Secure file upload', [
            'user_id' => auth()->id() ?? 'guest',
            'filename' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'directory' => $directory,
            'type' => $type,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Delete file securely
     *
     * @param string|null $path File path relative to storage/app/public
     * @return bool Success status
     */
    public function delete(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        try {
            $deleted = Storage::disk('public')->delete($path);
            
            if ($deleted) {
                Log::info('File deleted', [
                    'user_id' => auth()->id() ?? 'guest',
                    'path' => $path,
                    'ip' => request()->ip(),
                ]);
            }
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            return false;
        }
    }

    /**
     * Get allowed file types and sizes for validation rules
     *
     * @param string $type File type: 'image', 'document', or 'avatar'
     * @return array Validation rules
     */
    public static function getValidationRules(string $type = 'image'): array
    {
        if (!isset(self::FILE_TYPES[$type])) {
            $type = 'image';
        }

        $config = self::FILE_TYPES[$type];
        $maxKB = $config['max_size'] / 1024;

        return [
            'mimes:' . implode(',', array_map(function($mime) {
                return str_replace('image/', '', str_replace('application/', '', $mime));
            }, $config['extensions'])),
            'max:' . $maxKB,
        ];
    }

    /**
     * Get max file size for a type
     *
     * @param string $type File type
     * @return int Max size in bytes
     */
    public static function getMaxSize(string $type = 'image'): int
    {
        return self::FILE_TYPES[$type]['max_size'] ?? self::FILE_TYPES['image']['max_size'];
    }

    /**
     * Get allowed extensions for a type
     *
     * @param string $type File type
     * @return array Allowed extensions
     */
    public static function getAllowedExtensions(string $type = 'image'): array
    {
        return self::FILE_TYPES[$type]['extensions'] ?? self::FILE_TYPES['image']['extensions'];
    }
}
