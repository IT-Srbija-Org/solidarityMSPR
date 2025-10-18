<?php

namespace App\Tests\Controller\Delegate;

use App\DataFixtures\DamagedEducatorFixtures;
use App\DataFixtures\TenantFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\DamagedEducator;
use App\Repository\DamagedEducatorRepository;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PanelControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?UserRepository $userRepository;
    private ?DamagedEducatorRepository $damagedEducatorRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
        $this->damagedEducatorRepository = $container->get(DamagedEducatorRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            TenantFixtures::class,
            UserFixtures::class,
            DamagedEducatorFixtures::class,
        ]);
    }

    private function loginAsDelegate(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        if (!$user) {
            throw new \RuntimeException('Delegate user not found. Check UserFixtures for the correct email.');
        }

        $this->client->loginUser($user);
    }

    public function testDamagedEducatorsList(): void
    {
        $this->loginAsDelegate();

        $crawler = $this->client->request('GET', '/delegat/osteceni');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(2, $crawler->filter('table'));
        $this->assertAnySelectorTextContains('a.btn-primary', 'Dodaj');
    }

    public function testPeriodNewDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();

        $this->client->request('GET', '/delegat/prijavi-ostecenog');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
    }

    public function testNewDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();

        $crawler = $this->client->request('GET', '/delegat/prijavi-ostecenog');
        $form = $crawler->filter('form[name="damaged_educator_edit"]')->form([
            'damaged_educator_edit[name]' => 'Milan Janjic',
            'damaged_educator_edit[amount]' => 10000,
            'damaged_educator_edit[accountNumber]' => '265104031000361092',
        ]);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEditDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();

        $damagedEducator = $this->damagedEducatorRepository->findOneBy([]);

        $crawler = $this->client->request('GET', '/delegat/osteceni/'.$damagedEducator->getId().'/izmeni-podatke');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->filter('form[name="damaged_educator_edit"]')->form([
            'damaged_educator_edit[name]' => 'Milan Janjic',
            'damaged_educator_edit[amount]' => 50000,
            'damaged_educator_edit[accountNumber]' => '265104031000361092',
        ]);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $damagedEducator = $this->damagedEducatorRepository->findOneBy(['accountNumber' => '265104031000361092']);
        $this->assertEquals($damagedEducator->getAmount(), 50000);
    }

    public function testDeleteDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();
        $damagedEducator = $this->damagedEducatorRepository->findOneBy([]);

        $crawler = $this->client->request('GET', '/delegat/osteceni/'.$damagedEducator->getId().'/brisanje');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->filter('form[name="damaged_educator_delete"]')->form([
            'damaged_educator_delete[confirm]' => true,
            'damaged_educator_delete[comment]' => 'Test komentar',
        ]);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $damagedEducator = $this->damagedEducatorRepository->find($damagedEducator->getId());
        $this->assertEquals(DamagedEducator::STATUS_DELETED, $damagedEducator->getStatus());
    }
}
