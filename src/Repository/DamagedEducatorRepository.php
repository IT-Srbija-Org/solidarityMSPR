<?php

namespace App\Repository;

use App\Entity\DamagedEducator;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<DamagedEducator>
 */
class DamagedEducatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $cache, private TransactionRepository $transactionRepository)
    {
        parent::__construct($registry, DamagedEducator::class);
    }

    //    public function getFromUser(User $user): array
    //    {
    //        $userDelegateSchools = $user->getUserDelegateSchools();
    //
    //        $schoolIds = [];
    //        foreach ($userDelegateSchools as $userDelegateSchool) {
    //            $schoolIds[] = $userDelegateSchool->getSchool()->getId();
    //        }
    //
    //        return $this->findBy([
    //            'school' => $schoolIds,
    //        ]);
    //    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->leftJoin('e.tenant', 't')
            ->addSelect('t');

        if (!empty($criteria['name'])) {
            $qb->andWhere('e.name LIKE :name')
                ->setParameter('name', '%'.$criteria['name'].'%');
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['accountNumber'])) {
            $criteria['accountNumber'] = str_replace('-', '', $criteria['accountNumber']);

            $qb->andWhere('e.accountNumber LIKE :accountNumber')
                ->setParameter('accountNumber', '%'.$criteria['accountNumber'].'%');
        }

        if (!empty($criteria['createdBy'])) {
            $qb->andWhere('e.createdBy = :createdBy')
                ->setParameter('createdBy', $criteria['createdBy']);
        }

        if (!empty($criteria['tenant'])) {
            $qb->andWhere('e.tenant = :tenant')
                ->setParameter('tenant', $criteria['tenant']);
        }

        // Set the sorting
        $qb->orderBy('e.id', 'DESC');

        // Apply pagination only if $limit is set and greater than 0
        if ($limit && $limit > 0) {
            $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        }

        // Get the query
        $query = $qb->getQuery();

        // Create the paginator if pagination is applied
        if ($limit && $limit > 0) {
            $paginator = new Paginator($query, true);

            return [
                'items' => iterator_to_array($paginator),
                'total' => count($paginator),
                'current_page' => $page,
                'total_pages' => ceil(count($paginator) / $limit),
            ];
        }

        return [
            'items' => $query->getResult(),
            'total' => count($query->getResult()),
            'current_page' => 1,
            'total_pages' => 1,
        ];
    }

    public function getSumAmount(bool $useCache): int
    {
        return $this->cache->get('damaged-educator-getSumAmount', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('e');
            $qb = $qb->select('SUM(e.amount)');

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getTotals(bool $useCache): int
    {
        return $this->cache->get('damaged-educator-getTotals', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('e');
            $qb = $qb->select('COUNT(DISTINCT e.accountNumber)');

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }
}
