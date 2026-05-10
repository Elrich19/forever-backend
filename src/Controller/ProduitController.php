<?php

namespace App\Controller;

use App\Entity\Distributeur;
use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/produits')]
class ProduitController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProduitRepository $produits,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] Distributeur $user): JsonResponse
    {
        $items = array_map([$this, 'dump'], $this->produits->findByDistributeur($user));
        return new JsonResponse($items);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $p = new Produit();
        $p->setDistributeur($user);
        $this->hydrate($p, $data);
        $this->em->persist($p);
        $this->em->flush();
        return new JsonResponse($this->dump($p), 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $p = $this->produits->find($id);
        if (!$p || $p->getDistributeur()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Produit introuvable.'], 404);
        }
        $data = json_decode($request->getContent(), true) ?? [];
        $this->hydrate($p, $data);
        $this->em->flush();
        return new JsonResponse($this->dump($p));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $p = $this->produits->find($id);
        if (!$p || $p->getDistributeur()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Produit introuvable.'], 404);
        }
        $this->em->remove($p);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }

    private function hydrate(Produit $p, array $data): void
    {
        if (isset($data['nom'])) $p->setNom($data['nom']);
        if (array_key_exists('categorie', $data)) $p->setCategorie($data['categorie']);
        if (array_key_exists('description', $data)) $p->setDescription($data['description']);
        if (isset($data['imageUrls']) && is_array($data['imageUrls'])) {
            $p->setImageUrls($data['imageUrls']);
            // Synchronise imageUrl (cover) avec la première de la liste
            $p->setImageUrl($data['imageUrls'][0] ?? null);
        } elseif (array_key_exists('imageUrl', $data)) {
            $p->setImageUrl($data['imageUrl']);
            $p->setImageUrls($data['imageUrl'] ? [$data['imageUrl']] : []);
        }
        if (isset($data['prix'])) $p->setPrix((string) $data['prix']);
        if (isset($data['actif'])) $p->setActif((bool) $data['actif']);
    }

    public function dump(Produit $p): array
    {
        $urls = $p->getImageUrls();
        if (empty($urls) && $p->getImageUrl()) {
            $urls = [$p->getImageUrl()];
        }
        return [
            'id' => $p->getId(),
            'nom' => $p->getNom(),
            'categorie' => $p->getCategorie(),
            'description' => $p->getDescription(),
            'imageUrl' => $urls[0] ?? null,
            'imageUrls' => $urls,
            'prix' => $p->getPrix(),
            'actif' => $p->isActif(),
            'createdAt' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
