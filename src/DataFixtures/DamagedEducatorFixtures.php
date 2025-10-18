<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Amounts;
use App\DataFixtures\Data\Names;
use App\Entity\DamagedEducator;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class DamagedEducatorFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    private function generateName(): string
    {
        $firstName = Names::getFirstNames()[array_rand(Names::getFirstNames())];
        $lastName = Names::getLastNames()[array_rand(Names::getLastNames())];

        return $firstName.' '.$lastName;
    }

    /**
     * Generates a valid Serbian bank account number.
     *
     * @param string $bankCode The 3-digit bank code (defaults to "160" for Banca Intesa)
     *
     * @return string A valid Serbian bank account number
     */
    public function generateAccountNumber(string $bankCode = '160'): string
    {
        // Ensure bank code is 3 digits
        $bankCode = substr(str_pad($bankCode, 3, '0', STR_PAD_LEFT), 0, 3);

        // Generate a random account part (13 digits)
        $randomPart = str_pad(mt_rand(0, 9999999999999), 13, '0', STR_PAD_LEFT);

        // Combine bank code and random part to form the base
        $base = $bankCode.$randomPart;

        // Calculate control digits (mod97)
        $controlNumber = 0;
        $calcBase = 100;

        // Process in reverse order (from right to left)
        for ($x = strlen($base) - 1; $x >= 0; --$x) {
            $num = (int) $base[$x];
            $controlNumber = ($controlNumber + ($calcBase * $num)) % 97;
            $calcBase = ($calcBase * 10) % 97;
        }

        // Calculate control digits (98 minus remainder)
        $checkDigits = str_pad(98 - $controlNumber, 2, '0', STR_PAD_LEFT);

        // Return the complete account number
        return $base.$checkDigits;
    }

    public function load(ObjectManager $manager): void
    {
        // Set fixed seed for deterministic results
        mt_srand(1234);

        $tenants = $this->entityManager->getRepository(Tenant::class)->findAll();
        if (empty($tenants)) {
            throw new \RuntimeException('No tenants found!');
        }

        // Get all confirmed delegates
        $delegates = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->getQuery()
            ->getResult();

        if (empty($delegates)) {
            throw new \RuntimeException('No confirmed delegates found!');
        }

        // Generate 1-30 educators per school
        $count = mt_rand(1, 30);

        for ($i = 0; $i < $count; ++$i) {
            $educator = new DamagedEducator();
            $educator->setName($this->generateName());
            $educator->setCity('Test City');
            $educator->setAmount(Amounts::generate(30000, null, 15, 50000));
            $educator->setAccountNumber($this->generateAccountNumber());

            // Pick random confirmed delegate
            $delegate = $delegates[array_rand($delegates)];
            $educator->setCreatedBy($delegate);

            // Pick random tenant
            $tenant = $tenants[array_rand($tenants)];
            $educator->setTenant($tenant);

            $manager->persist($educator);
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [5];
    }
}
