<?php

namespace App\Repository;

use App\Document\Customer;
use App\Document\ImportReport;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<Customer>
 */
class ImportReportRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportReport::class);
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
    public function findOneByFile(string $fileName): ?ImportReport
    {
        return $this->findOneBy(['file' => $fileName]);
    }

    public function remove(ImportReport $processBar): void
    {
        $this->dm->remove($processBar);
    }

    public function flush(): void
    {
        $this->dm->flush();
//        $this->dm->clear();
    }

    public function persist(ImportReport $report): void
    {
        $this->dm->persist($report);
    }

    public function persistAndFlush(ImportReport $report)
    {
        $this->dm->persist($report);
        $this->dm->flush();
    }
}
