<?php

namespace App\Controller;

use App\Entity\Prospect;
use App\Entity\Visite;
use App\Repository\TunnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
class PublicTunnelController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TunnelRepository $tunnels,
    ) {}

    #[Route('/tunnels/{slug}', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $t = $this->tunnels->findOneBySlug($slug);
        if (!$t || $t->getStatut() !== \App\Entity\Tunnel::STATUT_PUBLIE) {
            return new JsonResponse(['error' => 'Tunnel introuvable.'], 404);
        }

        $d = $t->getDistributeur();
        $produits = [];
        foreach ($t->getTunnelProduits() as $tp) {
            $p = $tp->getProduit();
            if (!$p->isActif()) continue;
            $produits[] = [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'description' => $p->getDescription(),
                'imageUrl' => $p->getImageUrl(),
                'prix' => $p->getPrix(),
            ];
        }

        return new JsonResponse([
            'slugUnique' => $t->getSlugUnique(),
            'titrePage' => $t->getTitrePage(),
            'sousTitre' => $t->getSousTitre(),
            'texteCta' => $t->getTexteCta() ?? 'En savoir plus',
            'messageMerci' => $t->getMessageMerci(),
            'distributeur' => [
                'prenom' => $d->getPrenom(),
                'nom' => $d->getNom(),
                'slogan' => $d->getSlogan(),
                'telephoneWhatsapp' => $d->getTelephoneWhatsapp(),
            ],
            'produits' => $produits,
        ]);
    }

    #[Route('/tunnels/{slug}/visite', methods: ['POST'])]
    public function trackVisite(string $slug, Request $request): JsonResponse
    {
        $t = $this->tunnels->findOneBySlug($slug);
        if (!$t) {
            return new JsonResponse(['error' => 'Tunnel introuvable.'], 404);
        }
        $data = json_decode($request->getContent(), true) ?? [];
        $v = new Visite();
        $v->setTunnel($t);
        $v->setPage($data['page'] ?? 'home');
        $this->em->persist($v);
        $this->em->flush();
        return new JsonResponse(['ok' => true], 201);
    }

    #[Route('/tunnels/{slug}/prospects', methods: ['POST'])]
    public function submitProspect(string $slug, Request $request): JsonResponse
    {
        $t = $this->tunnels->findOneBySlug($slug);
        if (!$t || $t->getStatut() !== \App\Entity\Tunnel::STATUT_PUBLIE) {
            return new JsonResponse(['error' => 'Tunnel introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty($data['prenom'])) {
            return new JsonResponse(['error' => 'Prénom requis.'], 400);
        }
        if (empty($data['telephone']) && empty($data['email'])) {
            return new JsonResponse(['error' => 'Téléphone ou email requis.'], 400);
        }

        $p = new Prospect();
        $p->setTunnel($t);
        $p->setPrenom($data['prenom']);
        if (!empty($data['telephone'])) $p->setTelephone($data['telephone']);
        if (!empty($data['email'])) $p->setEmail($data['email']);

        // Snapshot du panier : on accepte deux formats pour rétrocompat
        //  - liste d'IDs (anciens prospects)             → quantité = 1
        //  - liste d'objets {id, quantity}               → format e-commerce
        // On filtre pour ne retenir que les produits appartenant réellement à CE tunnel
        // (sécurité : un prospect ne peut pas pousser des IDs arbitraires).
        $askedRaw = is_array($data['produitsInteresse'] ?? null) ? $data['produitsInteresse'] : [];
        if (!empty($askedRaw)) {
            $tunnelProduits = [];
            foreach ($t->getTunnelProduits() as $tp) {
                $prod = $tp->getProduit();
                $tunnelProduits[$prod->getId()] = $prod;
            }
            $snapshot = [];
            foreach ($askedRaw as $entry) {
                $pid = null;
                $qty = 1;
                if (is_string($entry)) {
                    $pid = $entry;
                } elseif (is_array($entry)) {
                    $pid = $entry['id'] ?? null;
                    $qty = max(1, (int) ($entry['quantity'] ?? 1));
                }
                if (!$pid || !isset($tunnelProduits[$pid])) continue;
                $prod = $tunnelProduits[$pid];
                $snapshot[] = [
                    'id'       => $prod->getId(),
                    'nom'      => $prod->getNom(),
                    'prix'     => $prod->getPrix(),
                    'quantity' => $qty,
                ];
            }
            $p->setProduitsInteresse($snapshot);
        }

        $this->em->persist($p);
        $this->em->flush();

        return new JsonResponse([
            'ok' => true,
            'message' => $t->getMessageMerci() ?? 'Merci ! Nous vous recontactons rapidement.',
        ], 201);
    }
}
