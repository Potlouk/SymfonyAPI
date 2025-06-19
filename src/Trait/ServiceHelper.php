<?php
namespace App\Trait;

use App\Exception\RequestBodyException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

trait ServiceHelper {
    
      private function extractValidatorErrors(ConstraintViolationListInterface $violations): array {
        foreach ($violations as $violation) {
            $errors[] = [
                'error' => $violation->getMessage(),
                'field' => $violation->getPropertyPath()
            ];
        }
        return $errors ?? [];
      }

      private function validateRequestData (array $data, Constraint $constraints): void {
        $validator = Validation::createValidator();
        $violations = $validator->validate($data, $constraints);

        if (count($violations) > 0)
        throw new RequestBodyException($this->extractValidatorErrors($violations));
      }
}