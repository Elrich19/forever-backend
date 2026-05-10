<?php

namespace App\Controller;

use App\Entity\Distributeur;
use App\Repository\DistributeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DistributeurRepository $distributeurs,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly JWTTokenManagerInterface $jwt,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/login', methods: ['POST'])]
    public function loginStub(): JsonResponse
    {
        // Cette route est interceptée par le firewall json_login.
        // Le code ci-dessous n'est jamais exécuté en pratique.
        return new JsonResponse(['error' => 'Authentification échouée'], 401);
    }

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        foreach (['prenom', 'nom', 'email', 'motDePasse'] as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Champ requis : $field"], 400);
            }
        }

        if ($this->distributeurs->findOneByEmail($data['email'])) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé.'], 409);
        }

        if (strlen($data['motDePasse']) < 8) {
            return new JsonResponse(['error' => 'Mot de passe trop court (8 caractères minimum).'], 400);
        }

        $d = new Distributeur();
        $d->setPrenom($data['prenom']);
        $d->setNom($data['nom']);
        $d->setEmail($data['email']);
        $d->setMotDePasseHash($this->hasher->hashPassword($d, $data['motDePasse']));
        if (!empty($data['telephoneWhatsapp'])) $d->setTelephoneWhatsapp($data['telephoneWhatsapp']);
        if (!empty($data['slogan'])) $d->setSlogan($data['slogan']);

        $errors = $this->validator->validate($d);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], 400);
        }

        $this->em->persist($d);
        $this->em->flush();

        return new JsonResponse([
            'token' => $this->jwt->create($d),
            'distributeur' => self::dump($d),
        ], 201);
    }

    #[Route('/me', methods: ['GET'])]
    public function me(#[\Symfony\Component\Security\Http\Attribute\CurrentUser] ?Distributeur $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }
        return new JsonResponse(self::dump($user));
    }

    #[Route('/me', methods: ['PUT', 'PATCH'])]
    public function updateMe(
        Request $request,
        #[\Symfony\Component\Security\Http\Attribute\CurrentUser] ?Distributeur $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }
        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['prenom'])) $user->setPrenom($data['prenom']);
        if (isset($data['nom'])) $user->setNom($data['nom']);
        if (array_key_exists('telephoneWhatsapp', $data)) $user->setTelephoneWhatsapp($data['telephoneWhatsapp']);
        if (array_key_exists('slogan', $data)) $user->setSlogan($data['slogan']);

        if (!empty($data['nouveauMotDePasse'])) {
            if (strlen($data['nouveauMotDePasse']) < 8) {
                return new JsonResponse(['error' => 'Mot de passe trop court (8 caractères minimum).'], 400);
            }
            $user->setMotDePasseHash($this->hasher->hashPassword($user, $data['nouveauMotDePasse']));
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], 400);
        }

        $this->em->flush();
        return new JsonResponse(self::dump($user));
    }

    public static function dump(Distributeur $d): array
    {
        return [
            'id' => $d->getId(),
            'prenom' => $d->getPrenom(),
            'nom' => $d->getNom(),
            'email' => $d->getEmail(),
            'telephoneWhatsapp' => $d->getTelephoneWhatsapp(),
            'slogan' => $d->getSlogan(),
            'createdAt' => $d->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
