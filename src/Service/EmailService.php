<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Report;
use App\Enum\MailTypes;
use App\Enum\OperationTypes;
use App\Factory\EmailFactory;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class EmailService {

    public function __construct(
        private MailerInterface $mailer,
        private CULogService    $logService,
        private UserRepository  $userRepository
        ) {
    }

     /**
      * Sends an email notification
      * 
      * Creates an email for the specified mail type and document/report,
      * adds sender and receiver, appends BCC recipients if needed,
      * and sends the email.
      *
      * @param string $receiver Email address of the recipient
      * @param MailTypes $type Type of email to send
      * @param Document|Report $document The document or report related to the email
      * @return void
      */
    public function send(string $receiver, MailTypes $type, Document|Report $document): void {
        $email = EmailFactory::build($type, $document);
        $email->from(new Address('reports@app.com', 'Property Assessments'));
        $email->to($receiver); 
        
        if ($document instanceof Report)
        $document =  $document->getDocument();
        if ($type === MailTypes::USER_SUBMITTED_NOTIFICATION)
        $this->appendBcc($email, $document);
         
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface){

        }
      
    }

    /**
     * Adds BCC recipients to an email
     * 
     * Finds the creator of the document and adds their BCC recipients
     * to the email if available.
     * 
     * @param TemplatedEmail $email The email to modify
     * @param Document $document The document to get creator information from
     * @return void
     */
    private function appendBcc(TemplatedEmail $email, Document $document): void{

        $creatorMail = $this->logService->getMailFromAction(
            OperationTypes::CREATED,
            $document
        );

        $user = $this->userRepository->findByEmail($creatorMail);
        
        if (!empty($user->getBccEmail()))
        $email->bcc(...$user->getBccEmail());
    }


}