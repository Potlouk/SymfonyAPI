<?php 
namespace App\Transformer;

use App\Entity\User;

class UserTransformer {

    public function transform(User $user): array {
        return [
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'data'  => $user->getData(),
            'role'  => [
                'id'    => $user->getRole()?->getId(),
                'name'  => $user->getRole()?->getName(),
            ],
            'permissions'       => $user->getRoles(),
            'assignedReports'   => $user->getAssignedReports()->map(fn($report) => $report->getUuId()->toString())->toArray(),
            'assignedDocuments' => $user->getAssignedDocuments()->map(fn($document) => $document->getUuId()->toString())->toArray(),
            'lastActive'        => $user->getToken()?->getLastUsedDate()?->format('Y-m-d H:i:s.u'),
        ];
    }

    /**
     * @param array<int,User> $roles
     */
    public function transformAll(array $roles): array {
        foreach($roles as $role)
        $results[] = $this->transform($role);

        return $results ?? [];
    }
}