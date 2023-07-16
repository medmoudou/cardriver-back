<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    #[Route('/api/stripe/public', name: 'app_stripe_key_public')]
    public function public(): JsonResponse
    {
        return new JsonResponse(['public_key' => $_ENV['STRIPE_PUBLIC_KEY']]);
    }
    #[Route('/api/stripe/client_secret', name: 'app_stripe_key_client')]
    public function private(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $stripe = new \Stripe\StripeClient($_ENV['STRIPE_PRIVATE_KEY']);

        $customer = $stripe->customers->create([
            'email' => $data['email'],
        ]);

        return new JsonResponse($stripe->paymentIntents->create([
            'amount' => $data['amount'],
            'currency' => 'eur',
            'customer' => $customer->id,
            'description' => 'Numéro d\'immatriculation : ' . $data['immatriculation'],
            // 'automatic_payment_methods' => [
            //     'enabled' => true,
            // ],
            'payment_method_types' => ['card'],
        ]));
    }
}
