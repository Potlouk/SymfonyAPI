<?php

namespace App\Controller;


use App\Service\DocumentService;
use App\Service\ImageService;
use App\Service\PropertyService;
use App\Trait\JsonResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/image')]
final class ImageController extends AbstractController {
 use JsonResponseTrait;

 public function __construct(private readonly ImageService $entityService) {}

    #[Route('/document/{uuid}', name: 'image_upload_document', requirements: ['uuid' => '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'], methods: ['POST'])]
    public function uploadImage(string $uuid, Request $request, DocumentService $documentService): Response{
        $documentService->get($uuid);
        $imagePaths = $this->entityService->save($request->files->all(), $uuid);
        return $this->responseOK($imagePaths);
    }

    #[Route('/property/{id}', name: 'image_upload_logo', requirements: ['id' => '\d+' ], methods: ['POST'])]
    public function uploadLogo(int $id, Request $request, PropertyService $propertyService): Response{
        $propertyService->get($id);
        $imagePath = $this->entityService->save($request->files->all(), $id);
        return $this->responseOK($imagePath);
    }

    #[Route('/settings', name: 'image_upload_settings', methods: ['POST'])]
    public function uploadSettings(Request $request): Response{
        $imagePath = $this->entityService->save($request->files->all(), 'settings');
        return $this->responseOK($imagePath);
    }

    #[Route('', name: 'image_delete', methods: ['DELETE'])]
    public function delete(Request $request): Response{
        $this->entityService->delete($request->toArray());
        return $this->ResponseOK();
    }


    #[Route('/{path}', name: 'image_download', requirements: ['path' => '.+'], methods: ['GET'])]
    public function download(string $path): Response{
        $image = $this->entityService->download($path);

        $response = new Response();
        $response->setContent($image['Body']->getContents());
        $response->headers->set('Content-Type', $image['ContentType'] ?? 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'inline; filename="' . basename($image) . '"');
        $response->headers->set('Cache-Control', 'public, max-age=31536000');

        return $response;

    }


    #[Route('/zip', name: 'image_download_zip', methods: ['POST'])]
    public function downloadZip(Request $request): Response{

        $requestData = $request->toArray();
        $zipData = $this->entityService->downloadZip($requestData);

        $response = new Response($zipData);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="pictures_'.$requestData['filename'].'.zip"');

        return $response;
    }


}
