<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileService
{
    private static $instance;

    /**
     * ذخیره فایل در سیستم ذخیره‌سازی.
     *
     * @param \Illuminate\Http\UploadedFile $file
     */
    public function saveFile($fileRand, $file, ?string $customPath = null): ?string
    {
        if (!$fileRand && !$customPath) {
            throw new \InvalidArgumentException('error save file');
        }

        $fileName = $this->generateFileName($file);

        $filePath = ($customPath) ? $customPath.$fileName : $this->getFilePath($fileRand, $fileName);
        $result = Storage::disk(env('FILESYSTEM_DISK'))->put($filePath, file_get_contents($file->getRealPath()));

        $File = File::create([
            'name' => $fileName,
            'file_path' => $filePath,
            'type' => $file->getClientMimeType(),
        ]);

        return $File->id;
    }

    /**
     * حذف فایل از سیستم ذخیره‌سازی.
     */
    public function deleteFileById(int $fileId): bool
    {
        $file = File::where('id', $fileId)->first();

        if ($file && Storage::disk(env('FILESYSTEM_DISK'))->exists($file->file_path)) {
            Storage::disk(env('FILESYSTEM_DISK'))->delete($file->file_path);
            $file->delete();

            return true;
        }

        return false;
    }

    public function compress($source, $destination, $quality)
    {
        $info = getimagesize($source);

        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
        } elseif ($info['mime'] == 'image/gif') {
            $image = imagecreatefromgif($source);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
            // PNGها کیفیت ندارند، پس باید اول به JPEG تبدیل شوند
            imagejpeg($image, $destination, $quality);

            return $destination;
        } else {
            throw new \Exception('Unsupported image type: '.$info['mime']);
        }

        imagejpeg($image, $destination, $quality);

        return $destination;
    }

    /**
     * @param int $fileId
     *
     * @return \Illuminate\Http\Response
     */
    public function getFileById($fileId, bool $compress = false)
    {
        $File = File::where('uuid', $fileId)->first();
        if ($File) {
            $filePath = $File->file_path;

            if (Storage::disk(env('FILESYSTEM_DISK'))->exists($filePath)) {
                $tempSource = tempnam(sys_get_temp_dir(), 'img_');
                file_put_contents($tempSource, Storage::disk(env('FILESYSTEM_DISK'))->get($filePath));

                $mimeType = Storage::disk(env('FILESYSTEM_DISK'))->mimeType($filePath);

                if ($compress && str_starts_with($mimeType, 'image/')) {
                    $tempDest = tempnam(sys_get_temp_dir(), 'cmp_').'.jpg';

                    $this->compress($tempSource, $tempDest, 25);

                    $compressedContents = file_get_contents($tempDest);

                    unlink($tempSource);
                    unlink($tempDest);

                    return response($compressedContents, 200)->header('Content-Type', 'image/jpeg');
                } else {
                    // بازگرداندن فایل بدون فشرده‌سازی
                    $fileContents = Storage::disk(env('FILESYSTEM_DISK'))->get($filePath);
                    unlink($tempSource);

                    return response($fileContents, 200)->header('Content-Type', $mimeType);
                }
            }
        }

        abort(404, 'File not found.');
    }

    /**
     * تولید نام فایل منحصر به فرد.
     */
    private function generateFileName(\Illuminate\Http\UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = preg_replace('/[^a-zA-Z0-9]/', '_', $fileName); // حذف کاراکترهای غیرمجاز
        $fileName = $fileName.'_'.uniqid().'.'.$extension;

        return $fileName;
    }

    /**
     * ایجاد مسیر فایل.
     *
     * @param int $fileRand
     */
    private function getFilePath($fileRand, string $fileName): string
    {
        return 'file/'.$fileRand.'_'.$fileName;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new FileService();
        }

        return self::$instance;
    }

    public function getFileUrlByUuid(string $uuid): ?string
    {
        $file = File::where('uuid', $uuid)->first();
        if (!$file) {
            return null;
        }

        if (Storage::disk(env('FILESYSTEM_DISK'))->exists($file->file_path)) {
            return Storage::disk(env('FILESYSTEM_DISK'))->url($file->file_path);
        }

        return null;
    }
}
