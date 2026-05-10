<?php

namespace App\Service;

use App\Repository\TunnelRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugGenerator
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly TunnelRepository $tunnels,
    ) {}

    public function fromBase(string $base, ?string $excludeId = null): string
    {
        $slug = strtolower((string) $this->slugger->slug($base));
        if ($slug === '') {
            $slug = 'tunnel';
        }

        $candidate = $slug;
        $i = 2;
        while ($this->tunnels->slugExists($candidate, $excludeId)) {
            $candidate = $slug.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
