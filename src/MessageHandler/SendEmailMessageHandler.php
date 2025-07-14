<?php
namespace App\MessageHandler;

use App\Message\SendEmailMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendEmailMessageHandler
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function __invoke(SendEmailMessage $message)
    {
        $email = (new Email())
            ->from('ton-adresse@example.com')
            ->to($message->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html(
                sprintf(
                    '<p>Bonjour,</p>
                    <p>Pour réinitialiser votre mot de passe, veuillez cliquer sur le lien suivant :</p>
                    <p><a href="%s">%s</a></p>
                    <p>Si vous n\'avez pas demandé de réinitialisation, ignorez cet email.</p>',
                    $message->getResetUrl(),
                    $message->getResetUrl()
                )
            );

        $this->mailer->send($email);
    }
}