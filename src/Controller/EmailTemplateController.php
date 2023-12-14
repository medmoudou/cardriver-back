<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailTemplateController extends AbstractController
{
    #[Route('/email/template', name: 'app_email_template')]
    public function index(): Response
    {
        return $this->render('emails/delivered.html.twig', [
            'user_name' => 'Moudou Mohammed',
            'date_delivery' => (new \DateTime())->format('d/m/Y - H:m'),
            'date_collect' => (new \DateTime())->format('d/m/Y - H:m'),
            'date_confirmation' => (new \DateTime())->format('d/m/Y - H:m'),
        ]);
    }
}
