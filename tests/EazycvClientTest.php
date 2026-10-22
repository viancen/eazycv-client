<?php

namespace EazycvClient\Tests;

use Eazycv_Error;
use EazycvClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class EazycvClientTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private $history = [];

    public function testConstructorRequiresApiKey(): void
    {
        $this->expectException(Eazycv_Error::class);
        $this->expectExceptionMessage('You must provide a Eazycv API key');

        new EazycvClient('', 'demo');
    }

    public function testConstructorRequiresCustomer(): void
    {
        $this->expectException(Eazycv_Error::class);
        $this->expectExceptionMessage('You must provide a Eazycv customer slug');

        new EazycvClient('api-key', null);
    }

    public function testConstructorNormalizesRootAndStoresSettings(): void
    {
        $client = $this->makeClient([], [
            'settings' => ['module' => 'crm'],
        ]);

        $this->assertSame('https://api.eazycv.cloud/', $client->root);
        $this->assertSame(['module' => 'crm'], $client->getSettings());
        $this->assertSame('api-key', $client->apiKey);
        $this->assertSame('demo', $client->customer);
    }

    public function testGetSendsExpectedHeadersAndQuery(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['users' => [['id' => 1]]])),
        ]);
        $client->setUserKey('user-token');

        $result = $client->get('users', ['page' => 2]);

        $this->assertSame(['users' => [['id' => 1]]], $result);

        $request = $this->history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.eazycv.cloud/users?page=2', (string) $request->getUri());
        $this->assertSame('user-token', $request->getHeaderLine('X-User'));
        $this->assertSame('api-key', $request->getHeaderLine('X-Authorization'));
        $this->assertSame('demo', $request->getHeaderLine('X-Customer'));
        $this->assertSame('json', $request->getHeaderLine('X-response-type'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testPostPutsAndDeleteSendJsonBodies(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['ok' => true])),
            new Response(200, [], json_encode(['updated' => true])),
            new Response(200, [], json_encode(['deleted' => true])),
        ]);

        $this->assertSame(['ok' => true], $client->post('items', ['name' => 'A']));
        $this->assertSame(['updated' => true], $client->put('items/1', ['name' => 'B']));
        $this->assertSame(['deleted' => true], $client->del('items/1'));

        $this->assertSame('POST', $this->history[0]['request']->getMethod());
        $this->assertSame('{"name":"A"}', (string) $this->history[0]['request']->getBody());

        $this->assertSame('PUT', $this->history[1]['request']->getMethod());
        $this->assertSame('{"name":"B"}', (string) $this->history[1]['request']->getBody());

        $this->assertSame('DELETE', $this->history[2]['request']->getMethod());
        $this->assertSame('', (string) $this->history[2]['request']->getBody());
    }

    public function testLoginUserStoresSessionTokenAsUserKey(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'session' => [
                    'token' => 'session-token',
                    'user_id' => 42,
                ],
            ])),
        ]);

        $session = $client->loginUser('user@example.com', 'secret', true, 'captcha-token');

        $this->assertSame('session-token', $session['token']);
        $this->assertSame('session-token', $client->userKey);
        $this->assertSame('api-key', $client->apiKey);

        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame([
            'email' => 'user@example.com',
            'password' => 'secret',
            'captcha' => 'captcha-token',
            'persistant' => true,
        ], $body);
        $this->assertSame('https://api.eazycv.cloud/users/login', (string) $this->history[0]['request']->getUri());
    }

    public function testLoginCandidateReplacesApiKeyWithSessionToken(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'session' => [
                    'token' => 'candidate-token',
                ],
            ])),
        ]);

        $session = $client->loginCandidate('candidate@example.com', 'secret');

        $this->assertSame('candidate-token', $session['token']);
        $this->assertSame('candidate-token', $client->apiKey);
        $this->assertNull($client->userKey);
        $this->assertSame(
            'https://api.eazycv.cloud/candidates/login',
            (string) $this->history[0]['request']->getUri()
        );
    }

    public function testLoginUserThrowsOnMissingSession(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['error' => 'nope'])),
        ]);

        $this->expectException(Eazycv_Error::class);
        $this->expectExceptionMessage('Invalid credentials');

        $client->loginUser('user@example.com', 'secret');
    }

    /**
     * @param array<int, Response> $responses
     * @param array<string, mixed> $options
     */
    private function makeClient(array $responses = [], array $options = []): EazycvClient
    {
        $this->history = [];
        $history = Middleware::history($this->history);

        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $options['http_client'] = new Client(['handler' => $handlerStack]);

        return new EazycvClient('api-key', 'demo', 'https://api.eazycv.cloud', $options);
    }
}
