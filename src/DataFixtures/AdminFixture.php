<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use App\Factory\TokenFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixture extends Fixture
{

    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager ): void
    {
        $permission = (new Permission)->setValue([
            'ROLE_LABEL_DOCUMENT',
            'ROLE_ASSIGN_DOCUMENT',
            'ROLE_REOPEN_DOCUMENT',
            'ROLE_GET_DOCUMENT',
            'ROLE_DELETE_DOCUMENT',
            'ROLE_PATCH_DOCUMENT',
            'ROLE_PAGINATE_DOCUMENT',
            'ROLE_SHARE_DOCUMENT',
            'ROLE_UNSHARE_DOCUMENT',
            'ROLE_CREATE_DOCUMENT',
            'ROLE_DELETE_USER',
            'ROLE_CREATE_USER',
            'ROLE_LIST_USER',
            'ROLE_GET_ROLE',
            'ROLE_DELETE_ROLE',
            'ROLE_PATCH_ROLE',
            'ROLE_CREATE_ROLE',
            'ROLE_LIST_ROLE',
            'ROLE_LABEL_REPORT',
            'ROLE_ASSIGN_REPORT',
            'ROLE_GET_REPORT',
            'ROLE_DELETE_REPORT',
            'ROLE_PATCH_REPORT',
            'ROLE_CREATE_REPORT',
            'ROLE_PAGINATE_REPORT',
            'ROLE_GET_TEMPLATE',
            'ROLE_DELETE_TEMPLATE',
            'ROLE_PATCH_TEMPLATE',
            'ROLE_CREATE_TEMPLATE',
            'ROLE_PAGINATE_TEMPLATE',
            'ROLE_GET_PROPERTY',
            'ROLE_DELETE_PROPERTY',
            'ROLE_PATCH_PROPERTY',
            'ROLE_CREATE_PROPERTY',
            'ROLE_PAGINATE_PROPERTY',
            'ROLE_DELETE_LABEL',
            'ROLE_PATCH_LABEL',
            'ROLE_CREATE_LABEL',
            'ROLE_LIST_LABEL',
            'ROLE_STATISTICS_PROPERTY',
            'ROLE_GET_SETTINGS',
            'ROLE_PUT_SETTINGS',
            'ROLE_LIST_PERMISSION',
            'ROLE_ALL_DOCUMENT',     
            'ROLE_ALL_REPORT',
            'ROLE_VIEW_IMAGES_DOCUMENT',
            'ROLE_UPDATE_IMAGES_DOCUMENT',
            'ROLE_DELETE_IMAGES_DOCUMENT',
            'ROLE_VIEW_IMAGES_DESCRIPTION_DOCUMENT',
            'ROLE_UPDATE_IMAGES_DESCRIPTION_DOCUMENT',
            'ROLE_VIEW_CATEGORIES_DOCUMENT',
            'ROLE_UPDATE_CATEGORIES_DOCUMENT',
            'ROLE_VIEW_TAGS_DOCUMENT',
            'ROLE_UPDATE_TAGS_DOCUMENT',
            'ROLE_VIEW_COST_DOCUMENT',
            'ROLE_UPDATE_COST_DOCUMENT',
            'ROLE_VIEW_NOTES_DOCUMENT',
            'ROLE_UPDATE_NOTES_DOCUMENT',
            'ROLE_PATCH_USER',
            'ROLE_GET_USER',
        ]);

        $role = (new Role)->setName('admin')->setPermissions($permission);
        $user = (new User)
        ->setEmail('test@test.com')
        ->setData([])
        ->setRole($role);
        
        $user->setPassword($this->hasher->hashPassword($user, 'admin'));
        $user->setToken(TokenFactory::build(['permissions'=> []]));
        $manager->persist($user);
        $manager->flush();
    }
}
