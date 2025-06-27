# Tests

# Tests unitaires (PestPHP)

Exemple :

```php
it('blocks IP in blockedCountries', function () {
    // Configuration with blocked country 'RU'
    $response = $this->get('/', ['REMOTE_ADDR' => '203.0.113.10']);
    expect($response->getStatusCode())->toBe(403);
})->with('configs', [
    ['blockedCountries' => ['RU']],
]);
```

# Tests d'intégration HTTP (WebTestCase)

```php
class GeoFilterIntegrationTest extends WebTestCase
{
    public function testHomeBlocked()
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['REMOTE_ADDR' => '198.51.100.5']);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }
}
```
