<?php

namespace App\Repository;

use App\Document\Customer;
use App\Document\ImportReport;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<Customer>
 */
class CustomerRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
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
    public function findOneByFile(string $fileName): ?Customer
    {
        return $this->findOneBy(['file' => $fileName]);
    }

    public function persist(Customer $customer)
    {
        $this->dm->persist($customer);
    }

    public function flush(): void
    {
        $this->dm->flush();
//        $this->dm->clear();
    }

    public function clear(): void
    {
        $this->dm->clear();
    }

    /**
     * @param $fullName
     * @param $email
     * @param $city
     * @return array
     */
    public function findCustomerByEmail($email): ?Customer
    {
        return $this->findOneBy(['email' => $email]);
    }
}
