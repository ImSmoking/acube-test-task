<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FileConversionJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileConversionJob>
 */
class FileConversionJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileConversionJob::class);
    }

    /**
     * @param FileConversionJob $conversionJob
     * @param bool $persist
     * @return void
     */
    public function save(FileConversionJob $conversionJob, bool $persist = true): void
    {
        $this->getEntityManager()->persist($conversionJob);

        if ($persist) {
            $this->getEntityManager()->flush();
        }
    }
}
