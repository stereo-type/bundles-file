<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Domain\Entity;

use Slcorp\CoreBundle\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<File>
 */
interface FileRepositoryInterface extends RepositoryInterface
{
    public function flush(): void;

    public function getCountSameFiles(string $contenthash, int $excludeId): int;

    /**
     * @return File[]
     */
    public function getFilesOlderThen(int $timestamp, string $component = 'user', string $filearea = 'draft'): array;
}
