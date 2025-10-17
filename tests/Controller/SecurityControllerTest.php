<?php

namespace App\Tests\Controller;

use App\DataFixtures\TenantFixtures;
use App\DataFixtures\UserFixtures;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->catchExceptions(true);
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            TenantFixtures::class,
            UserFixtures::class,
        ]);
    }

    private function getLoginUser(): ?UserInterface
    {
        return static::getContainer()->get('security.token_storage')->getToken()->getUser();
    }

    public function testLoginAndEmailSend(): void
    {
        $crawler = $this->client->request('GET', '/logovanje');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('button[type="submit"]');

        // Login
        $form = $crawler->selectButton('Uloguj se')->form([
            'email' => 'korisnik@gmail.com',
        ]);

        $this->client->submit($form);

        // Check are email is sent
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Link za logovanje');
        $this->assertEmailTextBodyContains($mailerMessage, 'Klikni na dugme ispod kako bi se ulogovao/la na svoj nalog');

        // Extract login link
        $crawler = new Crawler($mailerMessage->getHtmlBody());
        $loginLink = $crawler->filter('#link')->attr('href');

        // Click on login link
        $this->client->request('GET', $loginLink);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check if user is logged
        $user = $this->getLoginUser();
        $this->assertNotNull($user);
        $this->assertEquals('korisnik@gmail.com', $user->getEmail());
    }
}
