<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use ApiPlatform\Validator\ValidatorInterface;
use App\Repository\ReservationRepository;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StatsController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository,
        private TrackRepository $trackRepository,
        private UserPasswordHasherInterface $passwordEncoder,
    ) {
    }

    #[Route('/api/stats', name: 'app_stats')]
    public function index(Request $request): JsonResponse
    {
        // $data = json_decode($request->getContent());

        // $startDate = new DateTimeImmutable($data->startDate);
        // $endDate = new DateTimeImmutable($data->endDate);

        $orders = $this->reservationRepository->findAll();
        $today_orders = $this->reservationRepository->createQueryBuilder('r')
            ->select('r')
            ->where('DATE(r.createdAt) = CURRENT_DATE()')
            ->getQuery()
            ->getResult();


        $delivering = $this->trackRepository->findBy(['details' => 'Véhicule récuperé']);
        $today_delivering = $this->trackRepository->createQueryBuilder('r')
            ->select('r')
            ->where("DATE(r.createdAt) = CURRENT_DATE() AND r.details = 'Véhicule récuperé' ")
            ->getQuery()
            ->getResult();


        $delivered = $this->trackRepository->findBy(['details' => 'Véhicule livré']);
        $today_delivered = $this->trackRepository->createQueryBuilder('r')
            ->select('r')
            ->where("DATE(r.createdAt) = CURRENT_DATE() AND r.details = 'Véhicule livré' ")
            ->getQuery()
            ->getResult();


        $income = $this->reservationRepository->createQueryBuilder('r')
            ->select('SUM(r.price)')
            ->getQuery()
            ->getSingleScalarResult();
        $today_income = $this->reservationRepository->createQueryBuilder('r')
            ->select('SUM(r.price)')
            ->where("DATE(r.createdAt) = CURRENT_DATE() ")
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'orders' => count($orders),
            'today_orders' => count($today_orders),
            'delivering' => count($delivering),
            'today_delivering' => count($today_delivering),
            'delivered' => count($delivered),
            'today_delivered' => count($today_delivered),
            'income' => $income,
            'today_income' => $today_income,
        ]);
        // dd($reservations);

        // return $this->render('stats/index.html.twig', [
        //     'controller_name' => 'StatsController',
        // ]);
    }
}
