<?php

namespace Inavii\Instagram\Media;

class Media
{
    public const DESTINATION = DIRECTORY_SEPARATOR . 'inavii-social-feed' . DIRECTORY_SEPARATOR;

    public const IMAGE_SMALL = ['s' => 300];
    public const IMAGE_MEDIUM = ['m' => 768];
    public const IMAGE_LARGE = ['l' => 1024];

    protected const IMAGE_TYPE = '.jpg';
    protected const IMAGE_SIZE = ['l' => 1024, 'm' => 768];

    public function __construct()
    {
        include_once(ABSPATH . 'wp-admin/includes/image.php');

        $this->createDirectory();
    }

    public static function checkGDLibraryAvailability(): bool
    {
        return function_exists('gd_info') && extension_loaded('gd');
    }

    public static function baseDir(): string
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . self::DESTINATION;
    }

    public static function baseUrl(): string
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/' . str_replace('\\', '/', self::DESTINATION);
    }

    public static function assetImageUrl(string $imageName): string
    {
        return INAVII_INSTAGRAM_URL . 'assets/images/' . str_replace('\\', '/', $imageName);
    }

    private function createDirectory(): void
    {
        if (!file_exists(self::baseDir())) {
            if (!mkdir(self::baseDir(), 0775, true) && !is_dir(self::baseDir())) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', self::baseDir()));
            }
        }
    }

    public function getImageDir($mediaId): string
    {
        return self::baseDir() . $mediaId;
    }

    public function getImageUrl(int $mediaId): string
    {
        return self::baseUrl() . $mediaId . '/';
    }

    public static function mediaUrl($mediaId): array
    {
        $path = self::baseUrl() . $mediaId;

        return self::mediaPath($path);
    }

    public static function mediaDir($mediaId): array
    {
        $path = (new Media)->getImageDir($mediaId);

        return self::mediaPath($path);
    }

    private static function mediaPath($path): array
    {
        return [
            'full' => $path . self::IMAGE_TYPE,
            'medium' => $path . '-m' . self::IMAGE_TYPE,
            'large' => $path . '-l' . self::IMAGE_TYPE,
            'small' => $path . '-s' . self::IMAGE_TYPE,
        ];
    }

    public static function deleteImage(string $mediaId, string $size): void
    {
        if (Media::checkGDLibraryAvailability() === false && $size === 'full') {
            return;
        }

        $mediaPath = self::mediaDir($mediaId);

        if ($mediaPath) {
            if (file_exists($mediaPath[$size])) {
                unlink($mediaPath[$size]);
            }
        }
    }

    public static function delete(string $mediaId): void
    {
        $mediaPath = self::mediaDir($mediaId);

        if ($mediaPath) {
            foreach ($mediaPath as $value) {
                if (file_exists($value)) {
                    unlink($value);
                }
            }
        }
    }

    public static function imageExist($id): bool
    {
        return file_exists((new Media)->getImageDir($id) . self::IMAGE_TYPE);
    }

    public static function deleteMediaDirectory(): void
    {
        $baseDir = self::baseDir();

        if (is_dir($baseDir)) {
            self::deleteDirectory($baseDir);
        }
    }

    private static function deleteDirectory($dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
