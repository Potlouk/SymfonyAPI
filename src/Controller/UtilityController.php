<?php

namespace App\Controller;

use App\Action\BuildInformationAction;
use App\Action\GeneratePDFAction;
use App\Interface\CacheStorageInterface;
use App\Service\SettingService;
use App\Trait\JsonResponseTrait;
use App\Transformer\BuildInformationTransformer;
use App\Transformer\SettingsTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utility')]
final class UtilityController extends AbstractController {
 use JsonResponseTrait;

    #[Route('/version/front/{type}', name: 'get_build', methods: ['GET'])]
    public function getFrontVersion(string $type, BuildInformationTransformer $transformer, CacheStorageInterface $cache): Response {
        $build = $cache->get('AppModelBuildInformation', $type);

        if (null === $build){
            $build = BuildInformationAction::build($type);
            $buildData = $transformer->transform($build);
            $cache->save($build, $buildData);

        }else $buildData = $build;

        return $this->ResponseOK($buildData);
    }

    #[Route('/version/back', name: 'get_version', methods: ['GET'])]
    public function getBackVersion(): Response{
        $composerFile = dirname(__DIR__, 2) . '/composer.json';
        $apiVersion = json_decode($composerFile, true)['version'] ?? 'UNKNOWN';

        return $this->responseOK(['version' => $apiVersion]);
    }

    #[Route('/settings', name: 'get_settings', methods: ['GET'])]
    public function getSettings(SettingService $entityService, SettingsTransformer $transformer, CacheStorageInterface $cache): Response{
        $settings = $cache->get('AppEntitySetting', 1);
        
        if (null === $settings) {
            $settings = $entityService->get();
            $settingsData = $transformer->transform($settings);
            $cache->save($settings, $settingsData);
        }else $settingsData = $settings;

        return $this->responseOK($settingsData);
    }

    #[Route('/settings', name: 'put_settings', methods: ['PUT'])]
    public function putSettings(Request $request, SettingService $entityService): Response{
        $entityService->put($request->toArray());
        return $this->responseOK();
    }

    #[Route('/generate/pdf', name: 'post_pdf', methods: ['POST'])]
    public function generatePdf(Request $request, GeneratePDFAction $pdfGenerator): Response {
        $pdf = $pdfGenerator->generate($request->toArray());
        return $this->responsePdf($pdf);
    }
    
}
