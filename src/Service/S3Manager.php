<?php
namespace App\Service;

use App\Exception\LogicException;
use App\Interface\ImageManagerInterface;
use Aws\S3\S3Client;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3Manager implements ImageManagerInterface
{
    private S3Client $s3Client;
    private string $bucket;
    public function __construct()
    {
         $this->s3Client = new S3Client([
             'version'     => 'latest',
             'region'      => $_ENV['AWS_REGION'],
             'endpoint'    => $_ENV['AWS_ENDPOINT'],
             'use_path_style_endpoint' => false,
             'credentials' => [
                 'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                 'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
             ],
         ]);

        $this->bucket = $_ENV['AWS_BUCKET_NAME'];
    }

    public function uploadFile(UploadedFile $file, string $path): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = pathinfo(
            $file->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        if (empty($extension)) {
            $mimeType = $file->getMimeType();
            $extension = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'bin'
            };
        }

        $key = "{$path}/{$filename}.{$extension}";

        $this->s3Client->putObject([
            'Bucket'     => $this->bucket,
            'Key'        => $key,
            'SourceFile' => $file->getPathname(),
            'ACL'        => 'public-read',
        ]);

        return $key;
    }

    public function get(string $path): object {
        if (!$this->doesFileExist($path)) {
            throw new LogicException("File not found in S3: $path");
        }
        return $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key'    => $path,
        ]);
    }
    
    public function delete(string $path): void {
        if ($this->doesFileExist($path))
        $this->s3Client->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => $path,
        ]);
    }

    public function deleteFolder(string $folderPath): void {
        if (!str_ends_with($folderPath, '/')) {
            $folderPath .= '/';
        }

        $objects = $this->s3Client->listObjectsV2([
            'Bucket' => $this->bucket,
            'Prefix' => 'storage/'.$folderPath,
        ]);

        if (!isset($objects['Contents']) || empty($objects['Contents'])) {
            return;
        }

        $objectsToDelete = [];
        foreach ($objects['Contents'] as $object) {
            $objectsToDelete[] = [
                'Key' => $object['Key']
            ];
        }

        $this->s3Client->deleteObjects([
            'Bucket' => $this->bucket,
            'Delete' => [
                'Objects' => $objectsToDelete,
            ],
        ]);
    }

    /**
     * Check if a file exists in S3 bucket
     * @param string $path The file path to check
     * @return bool True if file exists, false otherwise
     */
    private function doesFileExist(string $path): bool
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $path,
            ]);
            return true;
        } catch (Exception) {
            return false;
        }
    }
}
