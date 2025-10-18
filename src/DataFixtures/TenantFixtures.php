<?php

namespace App\DataFixtures;

use App\Entity\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class TenantFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $tenants = [
            'Za prosvetu',
            'Protiv represije',
            'Za Studente',
        ];

        foreach ($tenants as $tenantName) {
            $tenant = new Tenant();
            $tenant->setName($tenantName);
            $manager->persist($tenant);
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [1];
    }
}
