<?php 

namespace App\Interface;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ImageManagerInterface {

    /** save object of uploaded file to specified path */
    public function uploadFile(UploadedFile $file, string $path): string;
    public function delete(string $path): void;
    public function deleteFolder(string $folderPath): void;
    /**
     * @return array<string, mixed> | object
     */
    public function get(string $path): array| object;


}