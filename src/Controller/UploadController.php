<?php

namespace App\Controller;

use App\Entity\Distributeur;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/uploads')]
class UploadController
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5 Mo
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
    ];
    private const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private readonly string $uploadDir,
    ) {}

    #[Route('/image', methods: ['POST'])]
    public function uploadImage(Request $request, #[CurrentUser] Distributeur $user): JsonResponse
    {
        $files = $request->files->get('files') ?? [];
        if (!is_array($files)) $files = [$files];
        $single = $request->files->get('file');
        if ($single) $files[] = $single;

        if (empty($files)) {
            return new JsonResponse(['error' => 'Aucun fichier reçu.'], 400);
        }

        $userDir = $this->uploadDir.'/'.$user->getId();
        if (!is_dir($userDir) && !mkdir($userDir, 0775, true) && !is_dir($userDir)) {
            return new JsonResponse(['error' => 'Impossible de créer le répertoire de stockage.'], 500);
        }

        $urls = [];
        foreach ($files as $file) {
            if (!$file) continue;

            if ($file->getSize() > self::MAX_BYTES) {
                return new JsonResponse(['error' => 'Image trop lourde (max 5 Mo).'], 400);
            }

            $mime = $file->getMimeType() ?? '';
            $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            if (!in_array($mime, self::ALLOWED_MIMES, true) && !in_array($ext, self::ALLOWED_EXTS, true)) {
                return new JsonResponse(['error' => 'Format non supporté. Utilisez JPG, PNG, WebP ou GIF.'], 400);
            }

            $name = bin2hex(random_bytes(8)).'.'.($ext ?: 'jpg');
            $file->move($userDir, $name);
            $urls[] = '/uploads/'.$user->getId().'/'.$name;
        }

        return new JsonResponse(['urls' => $urls], 201);
    }
}
