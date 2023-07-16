<?php

namespace App\Controller;

use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Address;
use App\Entity\Car;
use App\Entity\Reservation;
use App\Entity\Track;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\HttpFoundation\Request;

class ReservationController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private SecurityBundleSecurity $security,
    ) {
    }

    public function __invoke(Request $request, Reservation $reservation): Reservation
    {
        $data = json_decode($request->getContent());

        // $reservation = new Reservation();

        if ($request->isMethod('POST')) {
            $car = new Car();
            $car->setImmatriculation($data->vehicule->immatriculation);
            $car->setEngine($data->vehicule->motorisation);
            $car->setModel($data->vehicule->model);
            $car->setType($data->vehicule->type);
            $car->setCarCondition($data->vehicule->car_condition);
            $this->entityManager->persist($car);

            $addressFrom = new Address();
            $addressFrom->setFirstname($data->addressFrom->prenom);
            $addressFrom->setLastname($data->addressFrom->nom);
            $addressFrom->setEmail($data->addressFrom->email);
            $addressFrom->setPhone($data->addressFrom->tel);
            $addressFrom->setAddress($data->addressFrom->adresse);
            $addressFrom->setZipcode($data->addressFrom->zipcode);
            $addressFrom->setCity($data->addressFrom->ville);
            $addressFrom->setCountry($data->addressFrom->pays);
            $this->entityManager->persist($addressFrom);

            $addressTo = new Address();
            $addressTo->setFirstname($data->addressTo->prenom);
            $addressTo->setLastname($data->addressTo->nom);
            $addressTo->setEmail($data->addressTo->email);
            $addressTo->setPhone($data->addressTo->tel);
            $addressTo->setAddress($data->addressTo->adresse);
            $addressTo->setZipcode($data->addressTo->zipcode);
            $addressTo->setCity($data->addressTo->ville);
            $addressTo->setCountry($data->addressTo->pays);
            $this->entityManager->persist($addressTo);

            $reservation = new Reservation();
            $reservation->setOwner($this->security->getUser());
            $reservation->setAddressFrom($addressFrom);
            $reservation->setAddressTo($addressTo);
            $reservation->setCar($car);
            $reservation->setAvailableFrom(new DateTimeImmutable($data->vehicule->availableFrom));
            $reservation->setAvailableTo(new DateTimeImmutable($data->vehicule->availableTo));
            $reservation->setPaymentMethod($data->reservation->method);
            $reservation->setStatus($data->reservation->status);
            $reservation->setPrice($data->reservation->price);
            $reservation->setPriceHT($data->reservation->priceHT);
            $reservation->setPriceTTC($data->reservation->priceTTC);
            $reservation->setCreatedAt(new DateTimeImmutable());
            $this->entityManager->persist($reservation);

            $track = new Track();
            $track->setReservation($reservation);
            $track->setDetails('Commande effectuée');
            $this->entityManager->persist($track);

            if ($data->reservation->status == 'PAID') {
                $track = new Track();
                $track->setReservation($reservation);
                $track->setDetails('Paiement effectué');
                $this->entityManager->persist($track);
            }

            $this->entityManager->flush();
        } else if ($request->isMethod('PATCH')) {
            $reservation->setStatus($data->status);
            $this->entityManager->persist($reservation);

            $track = new Track();
            $track->setReservation($reservation);
            if ($data->status == 'PAID') {
                $track->setDetails('Paiement effectué');
            } else if ($data->status == 'CONFIRMED') {
                $track->setDetails('Commande confirmée');
            } else if ($data->status == 'COLLECTED') {
                $track->setDetails('Véhicule récuperé');
            } else if ($data->status == 'DELIVERED') {
                $track->setDetails('Véhicule livré');
            } else if ($data->status == 'CANCELED') {
                $track->setDetails('Commande annulée');
            }
            $this->entityManager->persist($track);

            $this->entityManager->flush();
        }

        // $this->entityManager->persist($notification);

        return $reservation;
    }
}
