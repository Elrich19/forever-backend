<?php

namespace App\Controller;

use App\Entity\Distributeur;
use App\Entity\Tunnel;
use App\Entity\TunnelProduit;
use App\Repository\ProduitRepository;
use App\Repository\ProspectRepository;
use App\Repository\TunnelRepository;
use App\Repository\VisiteRepository;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tunnels')]
class TunnelController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TunnelRepository $tunnels,
        private readonly ProduitRepository $produits,
        private readonly ProspectRepository $prospects,
        private readonly VisiteRepository $visites,
        private readonly SlugGenerator $slugGen,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] Distributeur $user): JsonResponse
    {
        $items = array_map(fn($t) => $this->dumpSummary($t), $this->tunnels->findByDistributeur($user));
        return new JsonResponse($items);
    }

    /**
     * Vérification temps réel de la disponibilité d'un slug (style TikTok).
     * Déclaré AVANT les routes /{id} pour gagner la priorité du matcher.
     */
    #[Route('/check-slug', methods: ['GET'])]
    public function checkSlug(Request $request, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $raw = trim((string) $request->query->get('slug', ''));
        $excludeId = $request->query->get('excludeId') ?: null;

        if ($raw === '') {
            return new JsonResponse(['disponible' => null, 'reason' => 'empty', 'message' => '']);
        }

        if (mb_strlen($raw) < 3) {
            return new JsonResponse([
                'disponible' => false,
                'reason' => 'too_short',
                'message' => 'Trop court (3 caractères minimum).',
            ]);
        }

        if (mb_strlen($raw) > 120) {
            return new JsonResponse([
                'disponible' => false,
                'reason' => 'too_long',
                'message' => 'Trop long (120 caractères max).',
            ]);
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $raw)) {
            return new JsonResponse([
                'disponible' => false,
                'reason' => 'invalid',
                'message' => 'Lettres minuscules, chiffres et tirets uniquement.',
            ]);
        }

        $exists = $this->tunnels->slugExists($raw, $excludeId);
        if (!$exists) {
            return new JsonResponse([
                'disponible' => true,
                'message' => 'Disponible !',
            ]);
        }

        // Slug pris : on suggère une variante libre
        $suggested = $this->slugGen->fromBase($raw, $excludeId);

        return new JsonResponse([
            'disponible' => false,
            'reason' => 'taken',
            'message' => 'Ce slug est déjà utilisé.',
            'suggested' => $suggested,
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['nomTunnel'])) {
            return new JsonResponse(['error' => 'nomTunnel requis.'], 400);
        }

        $t = new Tunnel();
        $t->setDistributeur($user);
        $t->setNomTunnel($data['nomTunnel']);

        $slugBase = !empty($data['slugUnique'])
            ? $data['slugUnique']
            : $user->getPrenom().'-'.$user->getNom().'-'.$data['nomTunnel'];
        $t->setSlugUnique($this->slugGen->fromBase($slugBase));

        // Persister AVANT hydrate : les TunnelProduit créés par hydrate
        // référencent $t et exigent que celui-ci soit déjà managé.
        $this->em->persist($t);

        $this->hydrate($t, $data, $user);

        $errors = $this->validator->validate($t);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], 400);
        }

        $this->em->flush();

        return new JsonResponse($this->dumpFull($t), 201);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $t = $this->ownedOr404($id, $user);
        if ($t instanceof JsonResponse) return $t;
        return new JsonResponse($this->dumpFull($t));
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $t = $this->ownedOr404($id, $user);
        if ($t instanceof JsonResponse) return $t;

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['slugUnique']) && $data['slugUnique'] !== $t->getSlugUnique()) {
            $newSlug = $this->slugGen->fromBase($data['slugUnique'], $t->getId());
            $t->setSlugUnique($newSlug);
        }

        $this->hydrate($t, $data, $user);
        $t->setUpdatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($t);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], 400);
        }

        $this->em->flush();
        return new JsonResponse($this->dumpFull($t));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $t = $this->ownedOr404($id, $user);
        if ($t instanceof JsonResponse) return $t;
        $this->em->remove($t);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }

    #[Route('/{id}/prospects', methods: ['GET'])]
    public function prospects(string $id, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $t = $this->ownedOr404($id, $user);
        if ($t instanceof JsonResponse) return $t;

        $items = array_map(fn($p) => [
            'id' => $p->getId(),
            'prenom' => $p->getPrenom(),
            'telephone' => $p->getTelephone(),
            'email' => $p->getEmail(),
            'statut' => $p->getStatut(),
            'produitsInteresse' => $p->getProduitsInteresse(),
            'soumisLe' => $p->getSoumisLe()->format(\DateTimeInterface::ATOM),
        ], $this->prospects->findByTunnel($t));

        return new JsonResponse($items);
    }

    private function ownedOr404(string $id, Distributeur $user): Tunnel|JsonResponse
    {
        $t = $this->tunnels->find($id);
        if (!$t || $t->getDistributeur()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Tunnel introuvable.'], 404);
        }
        return $t;
    }

    private function hydrate(Tunnel $t, array $data, Distributeur $user): void
    {
        if (isset($data['nomTunnel'])) $t->setNomTunnel($data['nomTunnel']);
        if (array_key_exists('titrePage', $data)) $t->setTitrePage($data['titrePage']);
        if (array_key_exists('sousTitre', $data)) $t->setSousTitre($data['sousTitre']);
        if (array_key_exists('texteCta', $data)) $t->setTexteCta($data['texteCta']);
        if (array_key_exists('messageMerci', $data)) $t->setMessageMerci($data['messageMerci']);
        if (isset($data['statut'])) $t->setStatut($data['statut']);

        if (isset($data['produits']) && is_array($data['produits'])) {
            $hadExisting = !$t->getTunnelProduits()->isEmpty();
            foreach ($t->getTunnelProduits() as $tp) {
                $this->em->remove($tp);
                $t->getTunnelProduits()->removeElement($tp);
            }
            // Flush uniquement si on avait des lignes à supprimer (évite les violations
            // d'unicité sur (tunnel_id, produit_id) lors d'un remplacement).
            if ($hadExisting) {
                $this->em->flush();
            }

            foreach ($data['produits'] as $idx => $line) {
                $produitId = is_array($line) ? ($line['produitId'] ?? null) : $line;
                $ordre = is_array($line) ? (int) ($line['ordreAffichage'] ?? $idx) : $idx;
                if (!$produitId) continue;

                $produit = $this->produits->find($produitId);
                if (!$produit || $produit->getDistributeur()->getId() !== $user->getId()) continue;

                $tp = new TunnelProduit();
                $tp->setTunnel($t);
                $tp->setProduit($produit);
                $tp->setOrdreAffichage($ordre);
                $this->em->persist($tp);
                $t->getTunnelProduits()->add($tp);
            }
        }
    }

    public function dumpSummary(Tunnel $t): array
    {
        return [
            'id' => $t->getId(),
            'nomTunnel' => $t->getNomTunnel(),
            'slugUnique' => $t->getSlugUnique(),
            'statut' => $t->getStatut(),
            'updatedAt' => $t->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'createdAt' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'nbVisites' => $this->visites->countByTunnel($t),
            'nbProspects' => $t->getProspects()->count(),
        ];
    }

    public function dumpFull(Tunnel $t): array
    {
        $produits = [];
        foreach ($t->getTunnelProduits() as $tp) {
            $p = $tp->getProduit();
            $produits[] = [
                'produitId' => $p->getId(),
                'nom' => $p->getNom(),
                'description' => $p->getDescription(),
                'imageUrl' => $p->getImageUrl(),
                'prix' => $p->getPrix(),
                'ordreAffichage' => $tp->getOrdreAffichage(),
            ];
        }

        return array_merge($this->dumpSummary($t), [
            'titrePage' => $t->getTitrePage(),
            'sousTitre' => $t->getSousTitre(),
            'texteCta' => $t->getTexteCta(),
            'messageMerci' => $t->getMessageMerci(),
            'produits' => $produits,
        ]);
    }
}
