<?php

namespace App\Factory;

use App\Entity\Document;
use App\Entity\Report;
use App\Enum\MailTypes;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class EmailFactory {

    /**
     * @param MailTypes $mailType
     * @param Report|Document $document
     * @return TemplatedEmail
     */
    public static function build(MailTypes $mailType, Document|Report $document): TemplatedEmail {
        $isReport = $document instanceof Report;

        $link = $isReport
            ? "a"
            : "b";

        $link .= "?uuid={$document->getUuid()}&token={$document->getToken()?->getValue()}";

        $document = $isReport ? $document->getDocument() : $document;

       return (new TemplatedEmail)
        ->subject(
            self::createSubject($mailType, $document)
        )->htmlTemplate(
            self::getTemplate($mailType)
        )->context([
            'link'          => $link,
            'propertyName'  => $document->getProperty()->getName() ?? 'Property',
            'footer'        => "",
            'header'        => self::setHeader($mailType, $document),
            'tenantEmail'   => $document->getToken()->getEmail(),
            'manager'       => $document->getProperty()->getData()['manager'] ?? 'Manager',
            'type'          => self::getDocumentType($document),
            'expireDate'    => $document->getToken()->getExpiryDate()?->format('F dS Y '),
            'expireDays'    => self::setDaysRemaining($document->getToken()->getExpiryDate()),
        ]);
    }

    private static function createSubject(MailTypes $mailType, Document $document) : string {
        $propertyName = $document->getProperty()?->getName() ?? 'Property';
        $documentType = $document->getTypeName() ?? 'document';

        return match ($mailType){
            MailTypes::DOCUMENT_SUBMISSION_CREATED, MailTypes::DOCUMENT_SUBMISSION_REOPENED
            => "{$propertyName} {$documentType} requirement",
            MailTypes::USER_SUCCESSFUL_SUBMIT    
            => "Thanks for submitting the {$documentType}",
            MailTypes::USER_SUBMITTED_NOTIFICATION           
            => "User submitted {$documentType} for {$propertyName}",
        };
    }

    private static function getTemplate(MailTypes $subject): string {
        return match ($subject){
            MailTypes::DOCUMENT_SUBMISSION_CREATED 
            => 'documentCreated.html.twig',
            MailTypes::DOCUMENT_SUBMISSION_REOPENED 
            => 'documentUpdated.html.twig',
            MailTypes::USER_SUCCESSFUL_SUBMIT    
            => 'documentUserHasSubmitted.html.twig',
            MailTypes::USER_SUBMITTED_NOTIFICATION           
            => 'documentSubmittedNotification.html.twig',
        };
    }

    private static function setHeader(MailTypes $subject, Document|Report $document): string {
        $receiverFullName = $document->getInfo()["sharedInfo"]["fullName"] ?? "";
        return (empty($receiverFullName)) ? $receiverFullName : match($subject){
            MailTypes::DOCUMENT_SUBMISSION_CREATED,
            MailTypes::DOCUMENT_SUBMISSION_REOPENED,
            MailTypes::USER_SUCCESSFUL_SUBMIT
            => "Hello {$receiverFullName},",
            MailTypes::USER_SUBMITTED_NOTIFICATION           
            => "",
        };
    }

    private static function getDocumentType(Document|Report $document): ?string {
        $info = $document->getInfo();
        $isReport = $document instanceof Report;

        if ($document->getType() === 'assessment')
            return $info['name'] ?? $document->getInfo()['sharedInfo']['name'] ?? '';

        if (isset($info['name']))
          return $isReport
              ? "{$info['name']} {$document->getDocument()?->getTemplate()?->getName()}"
              : "{$info['name']} {$document->getTemplate()->getName()}";

       return $isReport
           ? $document->getDocument()?->getTemplate()?->getName()
           : $document->getTemplate()->getName();
    }

    private static function setDaysRemaining(?DateTimeImmutable $date): int {
        if (null === $date) return 0;
        $daysRemaining = ceil(($date->getTimestamp() - (new DateTimeImmutable())->getTimestamp()) / 86400);
        return ($daysRemaining < 1) ? 0: $daysRemaining;
    }
}