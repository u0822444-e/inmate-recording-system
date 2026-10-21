<?php
declare(strict_types=1);

namespace App\Helpers;

class Upload
{
    /** Absolute filesystem path to the public uploads root. */
    private const ROOT = __DIR__ . '/../../public_html/uploads';

    /** Web-relative prefix used in <img src>. */
    public const WEB_PREFIX = 'uploads';

    /** Maximum avatar size in bytes (2 MB). */
    private const AVATAR_MAX_BYTES = 2 * 1024 * 1024;

    /** Allowed image MIME types mapped to their canonical extension. */
    private const AVATAR_TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    /**
     * Handle an avatar upload for the given user id.
     * Returns the relative path to store in users.avatar_path, or null on no upload.
     *
     * @throws \RuntimeException on invalid file or failed write
     */
    public static function saveAvatar(array $file, int $userId): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed (code ' . $file['error'] . ').');
        }
        if ($file['size'] > self::AVATAR_MAX_BYTES) {
            throw new \RuntimeException('Avatar must be 2 MB or smaller.');
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new \RuntimeException('File is not a valid image.');
        }

        if (!isset(self::AVATAR_TYPES[$info[2]])) {
            throw new \RuntimeException('Only JPG, PNG, and WebP are allowed.');
        }
        $ext = self::AVATAR_TYPES[$info[2]];

        $dir = self::ROOT . '/avatar';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create avatar directory.');
        }

        // Random suffix prevents collisions and defeats guessable URLs.
        $name = $userId . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not save avatar file.');
        }

        return self::WEB_PREFIX . '/avatar/' . $name;
    }

    /**
     * Delete an avatar file (best-effort).
     *
     * Only deletes files that resolve inside public_html/uploads/avatar/,
     * so a tampered users.avatar_path can't be used to unlink arbitrary files.
     */
    public static function deleteAvatar(?string $relative): void
    {
        if (!$relative) {
            return;
        }

        $path = self::ROOT . '/' . ltrim($relative, '/');
        $real = realpath($path);
        $allowedRoot = realpath(self::ROOT . '/avatar');

        if ($real === false || $allowedRoot === false) {
            return;
        }

        // Must live inside the avatar folder; reject any ../ traversal.
        if (strpos($real, $allowedRoot . DIRECTORY_SEPARATOR) !== 0) {
            return;
        }
        if (!is_file($real)) {
            return;
        }

        @unlink($real);
    }
}