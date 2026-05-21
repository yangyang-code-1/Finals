<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminDashboardControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/dashboard');

        $this->assertResponseIsSuccessful();
        // Optional: assert the page contains the Dashboard header
        $this->assertSelectorTextContains('h1', 'Dashboard');
    }
}
