<?php
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeoFilterIntegrationTest extends WebTestCase
{
    public function testAccessBlocked(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertNotEquals(200, $client->getResponse()->getStatusCode());
    }
}
