<?php

namespace App\Factory;

use App\Entity\Permission;
use App\Entity\Token;
use App\Exception\LogicException;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

final class TokenFactory {
    /**
     * @param array<string, mixed> $permissions
     * @param array<string, mixed> $receiver
     */
    public static function build(array $permissions = [], string $expDate = null, array $receiver = null , string $validDate = null): Token {

       if (!empty($expDate))
           try { $validatedExpDate = new DateTimeImmutable($expDate);} catch(Exception){
                throw new LogicException("Wrong date format provided {$expDate}");
       }

        if (!empty($validDate))
            try { $validatedValidDate = new DateTimeImmutable($expDate);} catch(Exception){
                throw new LogicException("Wrong date format provided {$validDate}");
            }

        return (new Token())
        ->setPermissions(
            (new Permission())->setValue($permissions)
        )->setValue(
            Uuid::uuid7()
        )->setExpiryDate(
            $validatedExpDate ?? null
        )->setActive(
            true
        )->setReceiver(
            $receiver ?? [ 'email' => 'not specified' ]
        )->setValidDate(
            $validatedValidDate ?? new DateTimeImmutable()
        );
    }
}