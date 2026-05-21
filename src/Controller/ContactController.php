<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request $request,
        MailerInterface $mailer,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $contactSent = $request->query->get('sent') === '1' || $request->query->get('sent') === 'true';

        if ($request->isMethod('POST')) {
            $tokenValue = (string) $request->request->get('_csrf_token', '');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('contact_form', $tokenValue))) {
                $this->addFlash('danger', 'Your session expired. Please try again.');
                return $this->redirectToRoute('app_contact');
            }

            $name = trim((string) $request->request->get('name', ''));
            $fromEmail = trim((string) $request->request->get('email', ''));
            $subject = trim((string) $request->request->get('subject', ''));
            $message = trim((string) $request->request->get('message', ''));

            $errors = [];
            if ($name === '') {
                $errors[] = 'Name is required.';
            }
            if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email is required.';
            }
            if ($subject === '') {
                $errors[] = 'Subject is required.';
            }
            if ($message === '') {
                $errors[] = 'Message is required.';
            }

            if ($errors !== []) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error);
                }
            } else {
                $to = (string) ($request->server->get('CONTACT_TO_EMAIL') ?: $_ENV['CONTACT_TO_EMAIL'] ?? '');
                $from = (string) ($request->server->get('CONTACT_FROM_EMAIL') ?: $_ENV['CONTACT_FROM_EMAIL'] ?? '');

                // Sensible defaults if env vars aren't set yet.
                if ($to === '') {
                    $to = 'contact@comms.example';
                }
                if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
                    $from = $to;
                }

                try {
                    $email = (new Email())
                        ->from($from)
                        ->to($to)
                        ->replyTo($fromEmail)
                        ->subject('[Contact] ' . $subject)
                        ->text(
                            "New contact message:\n\n"
                            . "Name: {$name}\n"
                            . "Email: {$fromEmail}\n"
                            . "Subject: {$subject}\n\n"
                            . "Message:\n{$message}\n"
                        );

                    $mailer->send($email);

                    return $this->redirectToRoute('app_contact', ['sent' => '1']);
                } catch (\Throwable $e) {
                    $this->addFlash('danger', 'We could not send your message right now. Please try again later.');
                }
            }
        }

        return $this->render('contact/index.html.twig', [
            'contactSent' => $contactSent,
        ]);
    }
}

