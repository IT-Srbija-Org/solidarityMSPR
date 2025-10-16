<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 *
 * @method Tenant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tenant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tenant[]    findAll()
 * @method Tenant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TenantRepository extends ServiceEntityRepository
{
    public const PAGE_SIZE = 20;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    public function search(array $criteria, int $page): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('t');

        if (!empty($criteria['name'])) {
            $queryBuilder->andWhere('t.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        $queryBuilder->orderBy('t.id', 'DESC');

        $query = $queryBuilder->getQuery();
        $query->setFirstResult(($page - 1) * self::PAGE_SIZE);
        $query->setMaxResults(self::PAGE_SIZE);

        return new Paginator($query);
    }
}
