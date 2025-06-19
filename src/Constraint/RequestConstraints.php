<?php

namespace App\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

final class RequestConstraints {

    public static function documentConstraintCreate(): Assert\Collection
    {
        return new Assert\Collection([
            'propertyId'    => [new Assert\Required(), new Assert\NotBlank(), new Assert\Type('integer')],
            'templateUuid'  => [new Assert\Required(), new Assert\NotBlank(), new Assert\Type('string')],
        ],null,null,true);
    }

    public static function documentConstraintImages(): Assert\Collection
    {
        return new Assert\Collection([
            'positions'    => new Assert\Required([new Assert\Type('array')]),
        ],null,null,true);
    }

    public static function documentConstraintChangeProperty(): Assert\Collection
    {
        return new Assert\Collection([
            'propertyId'    => new Assert\Required([new Assert\NotBlank(), new Assert\Type('int')]),
        ],null,null,true);
    }

    public static function documentConstraintCreateFromReport(): Assert\Collection
    {
        return new Assert\Collection([
            'data'           => new Assert\Required([new Assert\Type('array')]),
            'name'           => new Assert\Required([new Assert\NotBlank(), new Assert\Type('string')]),
            'type'           => new Assert\Required([new Assert\NotBlank(), new Assert\Type('string')]),
            'assessmentType' => new Assert\Required(),
        ],null,null,true);
    }

    public static function documentConstraintShare(): Assert\Collection
    {
        return new Assert\Collection([
            'permissions'  => [
                new Assert\NotBlank(),
                new Assert\Type('array')
            ],
            'expiryDate'   => [
                new Assert\NotBlank(), 
                new Assert\DateTime('Y-m-d H:i:s', 'Must be a valid date in format YYYY-MM-DD HH:MM:SS')
            ],
            'validDate'    => [
                new Assert\NotBlank(), 
                new Assert\DateTime('Y-m-d H:i:s', 'Must be a valid date in format YYYY-MM-DD HH:MM:SS')
            ],
            'receiver'     => new Assert\Collection([
                'email'    => [
                    new Assert\NotBlank(), 
                    new Assert\Email([
                        'message' => 'The email "{{ value }}" is not a valid email.',
                        'mode' => 'strict'
                    ])
                ],
                'fullName' => [new Assert\NotBlank(), new Assert\Type('string')],
                'notes'    => [new Assert\Type('string')],
            ],null,null,true),
        ],null,null,true);
    }
   
    public static function templateConstraintCreate(): Assert\Collection
    {
        return new Assert\Collection([
            'data'        => new Assert\Required([new Assert\Type('array')]),
            'info'        => new Assert\Required([new Assert\Type('array')]) ,
            'name'        => new Assert\Required([new Assert\NotBlank(), new Assert\Type('string')]),
        ],null,null,true);
    }

    public static function templateConstraintPatch(): Assert\Collection
    {
        return new Assert\Collection([
            'data'        =>  new Assert\Optional([new Assert\Type('array')]),
            'info'        =>  new Assert\Optional([new Assert\Type('array')]),
            'name'        =>  new Assert\Optional([new Assert\Type('string')]),
        ],null,null,true);
    }

    public static function propertyConstraintCreate(): Assert\Collection
    {
        return new Assert\Collection([
            'data'        => new Assert\Required([new Assert\Type('array')]),
            'archived'    => new Assert\Optional([new Assert\Type('boolean')]),
        ],null,null,true);
    }

    public static function propertyConstraintPatch(): Assert\Collection
    {
        return new Assert\Collection([
            'data'        =>  new Assert\Optional([new Assert\Type('array')]),
            'archived'    =>  new Assert\Optional([new Assert\Type('boolean')]),
        ],null,null,true);
    }

    public static function reportConstraintCreate(): Assert\Collection
    {
        return new Assert\Collection([
            'data'        => new Assert\Required(new Assert\Type('array')),
            'info'        => new Assert\Required(new Assert\Type('array')),
            'type'        => new Assert\Required(new Assert\Type('string')),
            'permissions' => new Assert\Required(new Assert\Type('array')),
        ],null,null,true);
    }
 
    public static function reportConstraintPatch(): Assert\Collection
    {
        return new Assert\Collection([
            'data'      => new Assert\Optional([new Assert\Type('array')]),
            'info'      => new Assert\Optional([new Assert\Type('array')]),
        ],null,null,true);
    }

    public static function userConstraintCreate(): Assert\Collection
    {
        return new Assert\Collection([
            'email'     => new Assert\Required([new Assert\NotBlank(), new Assert\Email()]),
            'password'  => new Assert\Required([new Assert\NotBlank(),new Assert\Type('string')]),
            'roleId'    => new Assert\Required([new Assert\NotBlank(),new Assert\Type('integer')]),
            'data'      => new Assert\Required([new Assert\Type('array')]),

        ],null,null,true);
    }

    public static function userConstraintPatch(): Assert\Collection
    {
        return new Assert\Collection([
            'email'     => new Assert\Optional([new Assert\NotBlank(), new Assert\Email()]),
            'password'  => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('string')]),
            'roleId'    => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('integer')]),
            'data'      => new Assert\Optional([new Assert\Type('array')]),
        ],null,null,true);
    }

    public static function roleConstraintCreate(): Assert\Collection
    {
        return new Assert\Collection([
            'name'        => new Assert\Required([ new Assert\NotBlank(), new Assert\Type('string')]),
            'permissions' => new Assert\Required([ new Assert\Type('array')]),
            'treeIds'     => new Assert\Required([new Assert\Type('array')]),
        ],null,null,true);
    }

    public static function roleConstraintPatch(): Assert\Collection
    {
        return new Assert\Collection([
            'name'        => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('string')]),
            'permissions' => new Assert\Optional([new Assert\Type('array')]),
            'treeIds'     => new Assert\Optional([new Assert\Type('array')]),
        ],null,null,true);
    }

    public static function pdfGeneratorConstraintPOST(): Assert\Collection
    {
        return new Assert\Collection([
            'html'        => new Assert\Required([ new Assert\NotBlank(), new Assert\Type('string')]),
        ],null,null,true);
    }
}

