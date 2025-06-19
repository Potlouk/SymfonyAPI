<?php
namespace App\Service;

use App\Interface\ImageManagerInterface;
use App\Trait\ServiceHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use ZipArchive;

class ImageService {
use ServiceHelper;

    private string $publicFolder;

    public function __construct( 
        private readonly ImageManagerInterface $imageManager,
    ){
        $this->publicFolder = 'storage';
    }

    /**
     * Saves uploaded images to storage
     * 
     * Validates the input data and saves each uploaded image to the specified subfolder.
     * Returns an array of paths to the saved images.
     * 
     * @param UploadedFile[] $images Array of uploaded image files
     * @param string $folderName Contains subfolder name
     * @return array<int, string> Array of paths to the saved images
     */
    public function save(array $images , string $folderName): array{
        $path = "{$this->publicFolder}/{$folderName}";
        foreach($images as $image){
            $uploadedImagesPaths[] = $this->imageManager->uploadFile(
                $image,
                $path
            );     
        }
        return $uploadedImagesPaths ?? [];
    }
    
    /**
     * Deletes images from storage
     * 
     * Validates the input data and deletes each image at the specified paths.
     * 
     * @param array<string, mixed> $data Contains paths to images to delete
     * @return void
     */
    public function delete(array $data): void {
        $constraint = new Assert\Collection([
            'paths' => [new Assert\NotBlank(),new Assert\Count(min: 1)]
        ],null,null,true);
        
        $this->validateRequestData($data, $constraint);
        
        foreach($data['paths'] as $image) {
            if ('/' === $image || '' === $image)
            continue;

            $this->imageManager->delete($image); 
        }
    }

    public function deleteFolder(string $folderName): void {
        $this->imageManager->deleteFolder($folderName);
    }

    /**
     * Downloads images from storage
     *
     * Validates the input data and deletes each image at the specified paths.
     *
     */
    public function download(string $path): object {
        $constraint = new Assert\Collection([
            'path' =>  new Assert\Required([new Assert\NotBlank(), new Assert\Type('string')])
        ],null,null,true);

        $this->validateRequestData(['path' => $path], $constraint);

      return $this->imageManager->get($path);
    }

    /**
     * Creates a ZIP file of images and returns it for download
     *
     * @param array $data Array containing paths to images to include in the ZIP
     * @return false | string
     */
    public function downloadZip(array $data): false | string {
        $constraint = new Assert\Collection([
            'paths' => [new Assert\NotBlank(), new Assert\Count(min: 1)],
            'filename' => new Assert\NotBlank()
        ], null, null, true);

        $this->validateRequestData($data, $constraint);

        $tempZipFile = tempnam(sys_get_temp_dir(), 'zip');
        /** @noinspection PhpComposerExtensionStubsInspection */
        $zip = new ZipArchive();

        /** @noinspection PhpComposerExtensionStubsInspection */
        if ($zip->open($tempZipFile, ZipArchive::CREATE) === true) {
            foreach ($data['paths'] as $path) {
                $sanitizedFileName = preg_replace('/[\/:*?"<>|]/', '-', basename($path['name']));
                $imageContent = $this->imageManager->get($path['path'])['Body']->getContents();
                $zip->addFromString($sanitizedFileName, $imageContent);
            }
            $zip->close();
        }

        $zipContent = file_get_contents($tempZipFile);
        unlink($tempZipFile);

        return $zipContent;
    }
}
