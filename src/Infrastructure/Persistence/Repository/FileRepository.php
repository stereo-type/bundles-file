<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Infrastructure\Persistence\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Slcorp\CoreBundle\Infrastructure\Repository\Traits\RepositoryTrait;
use Slcorp\FileBundle\Domain\Entity\File;
use Slcorp\FileBundle\Domain\Entity\FileRepositoryInterface;

/**
 * @extends ServiceEntityRepository<File>
 * @method File|null find(mixed $id, LockMode|int|null $lockMode = null, int|null $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array|null $orderBy = null)
 * @method File[] findAll()
 * @method File[] findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class FileRepository extends ServiceEntityRepository implements FileRepositoryInterface
{
    /**
     * @use RepositoryTrait<File>
     */
    use RepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }
}
