<?php

namespace LaravelDev\App\Helpers;

use Illuminate\Support\Facades\Storage;
use LaravelDev\App\Exceptions\Err;
use Symfony\Component\Uid\Ulid;

class AwsS3Helper
{
    protected const ALLOW_FILE_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'mp4', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf',
    ];

    protected const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pdf' => 'application/pdf',
    ];

    /**
     * @param string $fileName
     * @param string $fileContent
     * @param string|null $acl
     * @return void
     */
    public static function PutObject(string $fileName, string $fileContent, ?string $acl = 'public'): void
    {
        Storage::disk('s3')->put($fileName, $fileContent, $acl);
    }

    /**
     * @param string $fileName
     * @return string|null
     */
    public static function GetObject(string $fileName): ?string
    {
        return Storage::disk('s3')->get($fileName);
    }

    /**
     * @param string $fileName
     * @param int|null $minutes
     * @return string
     */
    public static function TemporaryUrl(string $fileName, ?int $minutes = 30): string
    {
        return Storage::disk('s3')
            ->temporaryUrl($fileName, now()->addMinutes($minutes));
    }

    /**
     * @param string $fileName
     * @param int|null $minutes
     * @param string|null $acl
     * @return array
     */
    public static function TemporaryUploadUrl(string $fileName, ?int $minutes = 30, ?string $acl = 'public-read'): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = self::MIME_TYPES[$extension] ?? 'application/octet-stream'; // 默认 MIME 类型

        $return = Storage::disk('s3')->temporaryUploadUrl(
            $fileName,
            now()->addMinutes($minutes),
            [
                'ACL' => $acl,
            ]
        );
        $return['headers']['Content-Type'] = $mimeType;
        unset($return['headers']['Host']);
        return $return;
    }

    /**
     * 获取上传的参数
     * @param string $dir
     * @param string $fileName
     * @return array
     * @throws Err
     */
    public static function PreUpload(string $dir, string $fileName): array
    {
        $fileExt = last(explode('.', $fileName));
        if (!in_array($fileExt, self::ALLOW_FILE_EXTENSIONS))
            ee('文件类型不允许上传');

        $id = Ulid::generate();
        return AwsS3Helper::temporaryUploadUrl("/upload/$dir/$id.$fileExt");
    }

    /**
     * 富文本中的图片替换为 OSS 地址
     * @param array $params
     * @param array $keys
     * @param string $uploadDir
     * @return void
     */
    public static function ReplaceImageToOss(array &$params, array $keys, string $uploadDir): void
    {
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                $params[$key] = preg_replace_callback('/(<img[^>]+src=")([^">]+)(")/i', function ($matches) use ($uploadDir) {
                    if ($matches[2] && strpos($matches[2], 'ata:image')) {
                        $url = self::uploadBase64Image($matches[2], $uploadDir);
                        return $matches[1] . $url . $matches[3];
                    }
                    return $matches[1] . $matches[2] . $matches[3];
                }, $params[$key]);
            }
        }
    }

    /**
     * @param string $content
     * @param string $uploadDir
     * @return string
     */
    private static function uploadBase64Image(string $content, string $uploadDir): string
    {
        $image = explode(',', $content);
        $content = base64_decode($image[1]);

        $extension = substr($image[0], strpos($image[0], "/") + 1);
        $extension = substr($extension, 0, strpos($extension, ";"));

        $id = Ulid::generate();
        $object = "/upload/$uploadDir/$id.$extension"; //sprintf("%s/%s", $uploadDir, uniqid(rand(1000, 9999)) . '.' . $extension);

        logger()->debug("replaceImageToOss: $object");
        self::PutObject($object, $content);
        return Storage::disk('s3')->url($object);
    }

    /**
     * 图片处理类，目前只适配了腾讯云
     * @param array $images
     * @param string $config
     * @return void
     */
    public static function ProcessImages(array &$images, string $config): void
    {
        $newImages = [];
        foreach ($images as $image) {
            if (!isset($image['thumb_key'])) {
                // 如果没有缩略图
                $image['key'] = last(explode("myqcloud.com", $image['url']));
                $image['thumb_key'] = str_replace('/upload/', '/upload/thumb_', $image['key']);
                $image['thumb_config'] = $config;
                $image['thumbUrl'] = str_replace('/upload/', '/upload/thumb_', $image['url']);
                $content = file_get_contents($image['url'] . $config);
                AwsS3Helper::PutObject($image['thumb_key'], $content);
            } elseif (!isset($image['thumbUrl']) && isset($image['thumb_url'])) {
                // 如果有缩略图，但没有缩略图 URL
                $image['thumbUrl'] = $image['thumb_url'];
                unset($image['thumb_url']);
            }
            $newImages[] = $image;
        }
        $images = $newImages;
    }
}
