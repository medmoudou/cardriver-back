<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class MeController extends AbstractController
{
    #[Route('/api/me', name: 'app_me')]
    public function me(#[CurrentUser] ?User $user, SerializerInterface $serializer)
    {
        $data = $serializer->serialize($user, JsonEncoder::FORMAT);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/api/me/reservations', name: 'app_me_reservations')]
    public function reservations(#[CurrentUser] ?User $user, SerializerInterface $serializer)
    {
        $data = $serializer->serialize($user->getReservations(), JsonEncoder::FORMAT, ["groups" => "user:read"]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
