<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DistanceController extends AbstractController
{
    #[Route('/api/distance', name: 'app_distance')]
    public function index(Request $request): JsonResponse
    {
        $client = new Client();
        $data = json_decode($request->getContent(), true);
        $requestUrl = "https://api.openrouteservice.org/v2/matrix/driving-car";
        $vtype = $data['vtype'];
        $data = [
            'locations' => [$data['from'], $data['to']],
            'metrics' => ['distance'],
            'resolve_locations' => 'true',
            'units' => 'km',
        ];

        try {
            $response = $client->post($requestUrl, [
                'headers' => [
                    'Authorization' => '5b3ce3597851110001cf624891c423c0b4bb4bed87f38c9b4f2f7213',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result !== null && isset($result['distances'][0][1])) {
                $distance = $result['distances'][0][1];
                $price = $this->calculatePrice($distance);

                if ($vtype == 6) {
                    $price *= 1.03;
                } elseif ($vtype == 7) {
                    $price *= 1.03;
                } elseif ($vtype == 8) {
                    $price *= 1.07;
                } elseif ($vtype == 9) {
                    $price *= 1.11;
                } elseif ($vtype == 10) {
                    $price *= 1.166;
                } elseif ($vtype == 11) {
                    $price *= 1.27;
                } elseif ($vtype == 12) {
                    $price *= 1.38;
                }

                return new JsonResponse(array(
                    'distance' => $distance,
                    'price_HT' => round($price),
                    'price_TTC' => round($price + ($price * 0.2))
                ));
            }
        } catch (\Exception $e) {
            echo $e;
        }

        return null;
    }
    private function calculatePrice(float $distance): float
    {
        if ($distance >= 0 && $distance <= 150) {
            $pricePerKm = 1.5;
        } elseif ($distance >= 151 && $distance <= 280) {
            $pricePerKm = 1.3;
        } elseif ($distance >= 281 && $distance <= 400) {
            $pricePerKm = 1.2;
        } elseif ($distance >= 401 && $distance <= 740) {
            $pricePerKm = 1.0;
        } elseif ($distance >= 741 && $distance <= 900) {
            $pricePerKm = 0.9;
        } else {
            $pricePerKm = 0.8;
        }

        $price = $distance * $pricePerKm;

        return max($price, 70);
    }
}
