<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccountControllerTest extends WebTestCase
{
    public function testAccountPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/account');

        self::assertResponseIsSuccessful();
    }

    public function testLegacySampleRedirectsToAccount(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sample');

        self::assertResponseRedirects('/account');
    }
}
