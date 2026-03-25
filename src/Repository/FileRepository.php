<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\File;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<File>
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * @param File $file
     * @param bool $persist
     * @return void
     */
    public function save(File $file, bool $persist = true): void
    {
        $this->getEntityManager()->persist($file);

        if ($persist) {
            $this->getEntityManager()->flush();
        }
    }
}
