<?php

namespace App\Controller;

use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Individual;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordEncoder,
    ) {
    }

    public function __invoke(Request $request, string $id = null, MailerInterface $mailer)
    {
        if ($request->isMethod('POST')) {

            $reqData = json_decode($request->getContent(), true);

            if ($this->userRepository->findByEmail($reqData['email'])) {
                throw new BadRequestHttpException("Votre adresse e-mail est déjà utilisée");
            }

            $user = new User();
            $user->setEmail($reqData['email']);
            $password = $this->passwordEncoder->hashPassword($user, $reqData['password']);
            $user->setPassword($password);
            $user->setPhone($reqData['phone']);
            $user->setUserType($reqData['type']);
            $user->setCreatedAt(new \DateTimeImmutable());

            if ($user->getUserType() === 'individual') {
                $individual = new Individual();
                $individual->setFirstName($reqData['firstname']);
                $individual->setLastName($reqData['lastname']);
                $individual->setUser($user);
                $this->entityManager->persist($individual);
            } elseif ($user->getUserType() === 'professional') {
                $professional = new Professional();
                $professional->setSiret($reqData['siret']);
                $professional->setSocietyName($reqData['society']);
                $professional->setUser($user);
                $this->entityManager->persist($professional);
            } else {
                throw new BadRequestHttpException("Invalid user type");
            }

            $this->entityManager->flush($user);

            if ($user->getId()) {
                $email = (new TemplatedEmail())
                    ->from(new Address('contact@cardriver-solutions.fr', 'Car Driver Solutions'))
                    ->to($reqData['email'])
                    ->subject('Bienvenue chez Car Driver Solutions !')
                    ->htmlTemplate('emails/welcome.html.twig')
                    ->context([
                        'user_name' => $user->getUserType() === 'individual' ? $reqData['firstname'] . ' ' . $reqData['lastname'] : $reqData['society']
                    ]);
                $mailer->send($email);
            }

            return $user;
        } else if ($request->isMethod('GET')) {
        } else if ($request->isMethod('PATCH')) {

            $user = $this->userRepository->find($id);

            $data = json_decode($request->getContent(), true);

            // dd($user);

            if (isset($data['email']) && $data['email'] != $user->getEmail()) {
                if ($this->userRepository->findBy(['email' => $data['email']])) {
                    throw new BadRequestHttpException("Email is already in use");
                }
                $user->setEmail($data['email']);
            }

            if (isset($data['password'])) {
                $password = $this->passwordEncoder->hashPassword($user, $data['password']);
                $user->setPassword($password);
            }

            if (isset($data['phone'])) {
                $user->setPhone($data['phone']);
            }

            if (isset($data['userType']) && $data['userType'] !== $user->getUserType()) {
                $user->setUserType($data['userType']);

                if ($user->getIndividual()) {
                    $this->entityManager->remove($user->getIndividual());
                }

                if ($user->getProfessional()) {
                    $this->entityManager->remove($user->getProfessional());
                }

                if ($user->getUserType() === 'individual') {
                    $individual = new Individual();
                    $individual->setFirstName($data['firstname'] ?? '');
                    $individual->setLastName($data['lastname'] ?? '');
                    $individual->setUser($user);
                    $this->entityManager->persist($individual);
                } elseif ($user->getUserType() === 'professional') {
                    $professional = new Professional();
                    $professional->setSiret($data['siret'] ?? '');
                    $professional->setSocietyName($data['society'] ?? '');
                    $professional->setUser($user);
                    $this->entityManager->persist($professional);
                } else {
                    throw new BadRequestHttpException("Invalid user type");
                }
            } else if (isset($data['userType']) && $data['userType'] == $user->getUserType()) {
                if ($user->getUserType() === 'individual') {
                    $individual = $user->getIndividual();
                    $individual->setFirstName($data['firstname'] ?? '');
                    $individual->setLastName($data['lastname'] ?? '');
                    $individual->setUser($user);
                    $this->entityManager->persist($individual);
                } elseif ($user->getUserType() === 'professional') {
                    $professional = $user->getProfessional();
                    $professional->setSiret($data['siret'] ?? '');
                    $professional->setSocietyName($data['society'] ?? '');
                    $professional->setUser($user);
                    $this->entityManager->persist($professional);
                }
            }

            $this->entityManager->flush();

            return $user;
        }
    }

    #[Route('/api/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = (new TemplatedEmail())
            ->from(new Address('contact@cardriver-solutions.fr', 'Car Driver Solutions'))
            ->to(new Address('contact@cardriver-solutions.fr', 'Car Driver Solutions'))
            ->replyTo($data['email'])
            ->subject('Vous avez un nouveau message')
            ->html('<p>' . $data['email'] . '</p>');

        try {
            $mailer->send($email);

            if ($email) {
                return new JsonResponse(['status' => 'sent']);
            } else {
                return new JsonResponse(['status' => 'failed']);
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['status' => $th]);
        }
    }
}
