<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailController extends AbstractController
{
    #[Route('/mail/send', name: 'mail_send', methods: ['GET', 'POST'])]
    public function sendMail(Request $request, MailerInterface $mailer): Response
    {
        $sent = false;
        if ($request->isMethod('POST')) {
            $to = $request->request->get('to');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            $email = (new Email())
                ->from('no-reply@example.com')
                ->to($to)
                ->subject($subject)
                ->text($message);

            $mailer->send($email);
            $sent = true;
        }
        return $this->render('mail/send.html.twig', [
            'sent' => $sent
        ]);
    }
}
