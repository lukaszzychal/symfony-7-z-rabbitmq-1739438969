<?php

namespace App\Repository;

use App\Document\Customer;
use App\Document\ImportProgressBar;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<Customer>
 */
class ImportProgressBarRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportProgressBar::class);
    }

//    /**
//     * @return Customer[] Returns an array of Customer objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Customer
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findOneByFile(string $fileName): ?ImportProgressBar
    {
        return $this->findOneBy(['file' => $fileName]);
    }

    public function remove(ImportProgressBar $processBar): void
    {
        $this->dm->remove($processBar);
    }

    public function flushAndClear(): void
    {
        $this->dm->flush();
        $this->dm->clear();
    }
}
