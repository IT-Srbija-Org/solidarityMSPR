<?php

namespace App\Repository;

use App\Entity\DamagedEducator;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $cache)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function search(array $criteria, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin('t.damagedEducator', 'e');

        if (isset($criteria['id'])) {
            $qb->andWhere('t.id = :id')
                ->setParameter('id', $criteria['id']);
        }

        if (isset($criteria['user'])) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $criteria['user']);
        }

        if (!empty($criteria['donor'])) {
            $qb->leftJoin('t.user', 'u')
                ->andWhere('u.email LIKE :donor')
                ->setParameter('donor', '%'.$criteria['donor'].'%');
        }

        if (!empty($criteria['educator'])) {
            $qb->andWhere('e.name LIKE :educator')
                ->setParameter('educator', '%'.$criteria['educator'].'%');
        }

        if (!empty($criteria['accountNumber'])) {
            $criteria['accountNumber'] = str_replace('-', '', $criteria['accountNumber']);

            $qb->andWhere('t.accountNumber LIKE :accountNumber')
                ->setParameter('accountNumber', '%'.$criteria['accountNumber'].'%');
        }

        if (isset($criteria['isUserDonorConfirmed'])) {
            $qb->andWhere('t.userDonorConfirmed = :isUserDonorConfirmed')
                ->setParameter('isUserDonorConfirmed', $criteria['isUserDonorConfirmed']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        // Set the sorting
        $qb->orderBy('t.id', 'DESC');

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

    public function getSumAmountTransactions(array $statuses): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb = $qb->select('SUM(t.amount)')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', $statuses);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function cancelAllTransactions(DamagedEducator $damagedEducator, string $comment, array $statuses, bool $checkDonorLastVisit): void
    {
        $transactions = $this->findBy([
            'damagedEducator' => $damagedEducator,
            'status' => $statuses,
        ]);

        foreach ($transactions as $transaction) {
            if ($checkDonorLastVisit) {
                $user = $transaction->getUser();
                if ($user->getLastVisit() && $user->getLastVisit() > $transaction->getCreatedAt()) {
                    continue;
                }
            }

            $transaction->setStatus(Transaction::STATUS_CANCELLED);
            $transaction->setStatusComment($comment);
        }

        $this->getEntityManager()->flush();
    }

    public function getSumAmountForAccountNumber(string $accountNumber, array $statuses): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb = $qb->select('SUM(t.amount)')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('t.accountNumber = :accountNumber')
            ->setParameter('accountNumber', $accountNumber)
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', $statuses);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getSumConfirmedAmount(bool $useCache): int
    {
        return $this->cache->get('transaction-getSumConfirmedAmount', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('t');
            $qb = $qb->select('SUM(t.amount)')
                ->andWhere('t.status = :status')
                ->setParameter('status', Transaction::STATUS_CONFIRMED);

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getTotalActiveDonors(bool $useCache): int
    {
        return $this->cache->get('transaction-getTotalActiveDonors', function (ItemInterface $item) {
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('t');
            $qb = $qb->select('COUNT(DISTINCT t.user)')
                ->andWhere('t.status = :status')
                ->setParameter('status', Transaction::STATUS_CONFIRMED);

            return (int) $qb->getQuery()->getSingleScalarResult();
        }, $useCache ? 1.0 : INF);
    }

    public function getPendingTransactions(): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb = $qb->select('t')
            ->innerJoin('t.damagedEducator', 'de')
            ->andWhere('t.status IN (:status)')
            ->setParameter('status', [
                Transaction::STATUS_WAITING_CONFIRMATION,
                Transaction::STATUS_EXPIRED,
            ])
            ->addOrderBy('de.id', 'ASC');

        $transactions = $qb->getQuery()->getResult();
        foreach ($transactions as $key => $transaction) {
            if (!$transaction->allowToChangeStatus()) {
                unset($transactions[$key]);
            }
        }

        return $transactions;
    }
}
