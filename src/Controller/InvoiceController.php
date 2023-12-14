<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceController extends AbstractController
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('/api/invoice/{id}', name: 'app_invoice')]
    public function index($id): Response
    {

        $orderNumber = $id;
        $order = $this->reservationRepository->findOneBy(['id' => $orderNumber]);
        $user = $this->userRepository->findOneBy(['id' => $order->getOwner()->getId()]);

        $company_address = '11 Av. Auguste Rodin
        94350, Villiers-sur-Marne
        N° SIRET : 99999999999999';

        if ($user->getIndividual()) {
            $customer_address = '<strong>' . $user->getIndividual()->getFirstname() . ' ' . $user->getIndividual()->getLastname() . '</strong><br>' .
                $user->getPhone() . '<br>' .
                $user->getEmail();
        } else {
            $customer_address = '<strong>' . $user->getProfessional()->getSocietyName() . '</strong><br> SIRET : ' . $user->getProfessional()->getSiret() . '<br>' .
                $user->getPhone() . '<br>' .
                $user->getEmail();
        }


        return $this->render('invoice.html.twig', [
            'customer_address' => nl2br($customer_address),
            'company_address' => nl2br($company_address),
            'invoice_number' => $orderNumber,
            'invoice_date' => $order->getCreatedAt()->format('d M Y'),
            'immatriculation' => $order->getCar()->getImmatriculation(),
            'addressFrom' =>  $order->getAddressFrom()->getCity(),
            'addressTo' => $order->getAddressTo()->getCity(),
            // 'km' => '-',
            'isPro' => $user->getProfessional() ? true : false,
            'totalHT' => $order->getPriceHT(),
            'TVA' => ($order->getPriceHT() * (20 / 100)),
            'totalTTC' => $order->getPriceTTC(),
        ]);
    }
}
