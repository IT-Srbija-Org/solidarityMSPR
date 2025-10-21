<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Amounts;
use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\UserDonor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class UserDonorFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    private array $comments = [
        'Podrška za prosvetare!',
        'Svaka čast na hrabrosti!',
        'Solidarnost je naša snaga!',
        'Zajedno smo jači!',
        null,
    ];

    public function load(ObjectManager $manager): void
    {
        // Set fixed seed for deterministic results
        mt_srand(1234);

        $tenants = $this->entityManager->getRepository(Tenant::class)->findAll();
        if (empty($tenants)) {
            throw new \RuntimeException('No tenants found!');
        }

        // Get all regular users (not admin, not delegate)
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->andWhere('u.roles NOT LIKE :admin')
            ->andWhere('u.roles NOT LIKE :delegate')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('delegate', '%ROLE_DELEGATE%')
            ->getQuery()
            ->getResult();

        // Shuffle users for random selection
        shuffle($users);

        // Ensure we have enough users for 36 donors
        if (count($users) < 36) {
            throw new \RuntimeException('Not enough users to create 36 donors!');
        }

        // Create 20 monthly donors
        for ($i = 0; $i < 20; ++$i) {
            $user = $users[$i];
            $userDonor = new UserDonor();
            $userDonor->setUser($user);
            $userDonor->setIsMonthly(true);
            $userDonor->setAmount(Amounts::generate(5000, null, 500, 100000));
            $userDonor->setComment($this->comments[array_rand($this->comments)]);
            $tenant = $tenants[array_rand($tenants)];
            $userDonor->setTenant($tenant);
            $manager->persist($userDonor);
        }

        // Create 16 non-monthly donors
        for ($i = 20; $i < 36; ++$i) {
            $user = $users[$i];
            $userDonor = new UserDonor();
            $userDonor->setUser($user);
            $userDonor->setIsMonthly(false);
            $userDonor->setAmount(Amounts::generate(5000, null, 500, 100000));
            $userDonor->setComment($this->comments[array_rand($this->comments)]);
            $tenant = $tenants[array_rand($tenants)];
            $userDonor->setTenant($tenant);
            $manager->persist($userDonor);
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [4];
    }
}
