<?php

namespace App\Traits;
use Illuminate\Support\Facades\Log;

trait FileProcessTrait
{

    /**
     * @incomingParam $file containing media file
     * @incomingParam $folderPath containing media file saving folder path. Ex. user/avatar/xyz.jpg
     * @incomingParam $resizeRequired containing true or false. True means Resize as per height & width either save original file into disk
     * @incomingParam $height & $width containing resize value as pixel
     *
     * */
    public function fileProcess(
        $file,
        $folderPath,
        $resizeRequired = true,
        $height = 800,
        $width  = 800
    )
    {
        // Folder Path Defining
        $dynamicPath = public_path($folderPath);

        // Dynamic Directory creating with Permissions
        if (!file_exists($dynamicPath)) {
            if (!mkdir($dynamicPath, 0777, TRUE) && !is_dir($dynamicPath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dynamicPath));
            }
        }

        // Selected File Extension
        $extension = $file->getClientOriginalExtension();

        // File Name generate
        $fileName  = convertToSlug($file->getClientOriginalName())  . "-".randomStringNumberGenerator(12,true,true). ".$extension";

        // File Path generate Ex. uploads/categories/xyz123.webp
        $filePath = "{$folderPath}{$fileName}";

        Log::info("File Saving : ".$filePath);

        $file->move($dynamicPath, $fileName);

        return $filePath;

    }

    /**
     * @incomingParams $filePath contains folder destination with file name
     * @incomingParams $disk may receive third party or custom file disk name.
     * */
    public function unlink($pathWithFileName, $disk = null)
    {
        Log::info("Unlink Path : " . $pathWithFileName);

        if (Storage::disk("public")->exists($pathWithFileName)) {
            Storage::disk("public")->delete($pathWithFileName);
        }
    }

    public function disk()
    {

    }

    public function fileRename()
    {
        return randomStringNumberGenerator(10,true,true,false);
    }
}
