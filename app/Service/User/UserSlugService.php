<?php

namespace App\Service\User;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Normalizer;

class UserSlugService implements UserSlugServiceInterface
{
    private const int    MAX_BASE_SLUG_LENGTH = 48;
    private const string FALLBACK_SLUG        = 'user';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function generateBaseSlug(string $name): string
    {
        // NFC first, so a name typed with combining marks and one typed precomposed share a slug
        $normalizedName = Normalizer::normalize($name, Normalizer::FORM_C);
        if ($normalizedName === false) {
            return self::FALLBACK_SLUG;
        }

        $slug = preg_replace('/[^\pL\pM\pN_]+/u', '-', mb_strtolower($normalizedName));
        if ($slug === null) {
            return self::FALLBACK_SLUG;
        }

        $slug = trim(mb_substr(trim($slug, '-'), 0, self::MAX_BASE_SLUG_LENGTH), '-');

        return $slug === '' ? self::FALLBACK_SLUG : $slug;
    }

    public function findAvailableSlug(string $name, ?int $exceptUserId = null): string
    {
        $baseSlug = $this->generateBaseSlug($name);

        $slug = $baseSlug;
        for ($suffix = 2; $this->userRepository->isSlugTaken($slug, $exceptUserId); $suffix++) {
            $slug = sprintf('%s-%d', $baseSlug, $suffix);
        }

        return $slug;
    }

    public function isBaseSlugTaken(string $name, ?int $exceptUserId = null): bool
    {
        return $this->userRepository->isSlugTaken($this->generateBaseSlug($name), $exceptUserId);
    }
}
