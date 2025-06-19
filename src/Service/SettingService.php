<?php

namespace App\Service;

use App\Entity\Setting;
use App\Exception\ResourceNotFoundException;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SettingService {

    public function __construct(
        private SettingRepository      $settingRepository,
        private EntityManagerInterface $entityManager,
        private ImageService           $imageService,
    ) {}

    /**
     * Retrieves settings by ID
     * 
     * @param int $id Settings ID, defaults to 1
     * @return Setting The requested settings entity
     * @throws ResourceNotFoundException When settings cannot be found
     */
    public function get(int $id = 1): Setting {
       return $this->settingRepository->findById($id);
    }

    /**
     * Updates settings data
     * 
     * Updates the settings data.
     * 
     * @param array<string, mixed> $data New settings data
     * @param int $id Settings ID, defaults to 1
     * @return Setting The updated settings entity
     */
    public function put(array $data, int $id = 1): Setting {
        $settings = $this->get($id);
        if (array_key_exists('data', $data)){
           if (!($data['data']['company']['logo'] ?? false) && null !== $settings->getLogo())
                $this->imageService->delete(['paths' => [ $settings->getLogo() ]]);

            $settings->setData($data['data']);
        }

        $this->entityManager->flush();
        return $settings;
    }
}