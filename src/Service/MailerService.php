<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendEmail($content = ''): void
    {
        $email = (new Email())
            ->from('coachingbyviko@gmail.com')
            ->to('v.ribolowsky@gmail.com')
            ->subject('Nouvelle réservation')
            ->text($content);

        $this->mailer->send($email);
    }
}
