<?php

namespace App\Controller;

use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Address;
use App\Entity\Car;
use App\Entity\Reservation;
use App\Entity\Track;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ReservationController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private SecurityBundleSecurity $security,
    ) {
    }

    public function __invoke(#[CurrentUser] ?User $user, Request $request, MailerInterface $mailer): Reservation
    {
        $data = json_decode($request->getContent());

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

            if ($reservation->getId()) {
                $message = (new TemplatedEmail())
                    ->from(new EmailAddress('contact@cardriver-solutions.fr', 'Car Driver Solutions'))
                    ->to($user->getEmail())
                    ->subject('Votre commande est en cours de traitement #' . $reservation->getId())
                    ->htmlTemplate('emails/confirmation.html.twig')
                    ->context([
                        'user_name' => $user->getUserType() === 'individual' ? ($user->getIndividual()->getFirstname() . ' ' . $user->getIndividual()->getLastname()) : ($user->getProfessional() ? $user->getProfessional()->getSocietyName() : ''),
                        'order' => $reservation->getId(),
                        'immatriculation' => $reservation->getCar()->getImmatriculation(),
                    ]);
                $mailer->send($message);
            }
        } else if ($request->isMethod('PATCH')) {

            $reservation = $this->entityManager->getRepository(Reservation::class)->find($request->get('id'));
            $tracks = $reservation->getTracks()->toArray();
            $reservation->setStatus($data->status);
            $this->entityManager->persist($reservation);

            $track = new Track();
            $track->setReservation($reservation);
            if ($data->status == 'PAID') {
                $track->setDetails('Paiement effectué');
            } else if ($data->status == 'CONFIRMED') {
                $track->setDetails('Commande confirmée');
                $message = (new TemplatedEmail())
                    ->from(new EmailAddress('contact@cardriver-solutions.fr', 'Car Driver Solutions'))
                    ->to($reservation->getOwner()->getEmail())
                    ->subject('Confirmation de votre commande #' . $reservation->getId())
                    ->htmlTemplate('emails/confirmed.html.twig')
                    ->context([
                        'user_name' => $reservation->getOwner()->getUserType() === 'individual' ? ($reservation->getOwner()->getIndividual()->getFirstname() . ' ' . $reservation->getOwner()->getIndividual()->getLastname()) : ($reservation->getOwner()->getProfessional() ? $reservation->getOwner()->getProfessional()->getSocietyName() : ''),
                        'date_confirmation' => (new \DateTime())->format('d/m/Y - H:i'),
                        'immatriculation' => $reservation->getCar()->getImmatriculation(),
                    ]);
                $mailer->send($message);
            } else if ($data->status == 'COLLECTED') {

                $track->setDetails('Véhicule récuperé');

                $confirmationDate = '';
                foreach ($tracks as $t) {
                    if ($t->getDetails() === 'Commande confirmée') {
                        $confirmationDate = $t->getCreatedAt()->format('d/m/Y - H:i');
                    }
                }

                $message = (new TemplatedEmail())
                    ->from(new EmailAddress('contact@cardriver-solutions.fr', 'Car Driver Solutions'))
                    ->to($reservation->getOwner()->getEmail())
                    ->subject('Votre véhicule est en chemin #' . $reservation->getId())
                    ->htmlTemplate('emails/collected.html.twig')
                    ->context([
                        'user_name' => $reservation->getOwner()->getUserType() === 'individual' ? ($reservation->getOwner()->getIndividual()->getFirstname() . ' ' . $reservation->getOwner()->getIndividual()->getLastname()) : ($reservation->getOwner()->getProfessional() ? $reservation->getOwner()->getProfessional()->getSocietyName() : ''),
                        'date_collect' => (new \DateTime())->format('d/m/Y - H:i'),
                        'date_confirmation' => $confirmationDate,
                        'immatriculation' => $reservation->getCar()->getImmatriculation(),
                    ]);
                $mailer->send($message);
            } else if ($data->status == 'DELIVERED') {
                $track->setDetails('Véhicule livré');

                $collectDate = '';
                $confirmationDate = '';
                foreach ($tracks as $t) {

                    if ($t->getDetails() === 'Véhicule récuperé') {
                        $collectDate = $t->getCreatedAt()->format('d/m/Y - H:i');
                    }

                    if ($t->getDetails() === 'Commande confirmée') {
                        $confirmationDate = $t->getCreatedAt()->format('d/m/Y - H:i');
                    }
                }

                $message = (new TemplatedEmail())
                    ->from(new EmailAddress('contact@cardriver-solutions.fr', 'Car Driver Solutions'))
                    ->to($reservation->getOwner()->getEmail())
                    ->subject('Votre véhicule est livré #' . $reservation->getId())
                    ->htmlTemplate('emails/delivered.html.twig')
                    ->context([
                        'user_name' => $reservation->getOwner()->getUserType() === 'individual' ? ($reservation->getOwner()->getIndividual()->getFirstname() . ' ' . $reservation->getOwner()->getIndividual()->getLastname()) : ($reservation->getOwner()->getProfessional() ? $reservation->getOwner()->getProfessional()->getSocietyName() : ''),
                        'date_delivery' => (new \DateTime())->format('d/m/Y - H:i'),
                        'date_collect' => $collectDate,
                        'date_confirmation' => $confirmationDate,
                        'immatriculation' => $reservation->getCar()->getImmatriculation(),
                    ]);
                $mailer->send($message);
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
