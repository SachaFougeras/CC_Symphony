<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClientControllerTest extends WebTestCase
{
    private function createAdminUser()
    {
        $user = new \App\Document\Client();
        $user->setNom('Marc Zuckerberg');
        $user->setEmail('marc@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password'); // Simulez un mot de passe fictif

        $dm = static::getContainer()->get('doctrine_mongodb')->getManager();
        $dm->persist($user);
        $dm->flush();

        return $user;
    }



    public function testIndex(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/clients');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des clients');
    }



}