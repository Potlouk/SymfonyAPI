<?php

namespace App\Controller;

use App\Service\StatsService;
use App\Trait\JsonResponseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/statistics')]
final class StatsController extends AbstractController {
 use JsonResponseTrait;

    #[Route('/property/{id}', name: 'statistics_property', requirements: ['id' => '\d+' ], methods: ['GET'])]
    public function getPropertyStats(int $id, Request $request, StatsService $entityService): Response{
        if ($request->query->has('filters')) 
        $filters = array_map('intval', explode(',', $request->query->get('filters')));
        
        $stats = $entityService->property($id, $filters ?? []);
        return $this->responseOK($stats);
    }
}
