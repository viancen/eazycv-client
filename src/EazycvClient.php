<?php

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * HTTP API client for EazyCV.
 *
 * This class is the stable public surface used by consuming applications.
 * Constructor arguments, public methods/properties, request headers, payload
 * encoding and decoded response shapes must remain backwards compatible.
 */
class EazycvClient
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Customer / instance slug sent as X-Customer.
     *
     * @var string
     */
    public $customer;

    /**
     * API key sent as X-Authorization.
     *
     * @var string
     */
    public $apiKey;

    /**
     * Optional user session token sent as X-User.
     *
     * @var string|null
     */
    public $userKey = null;

    /**
     * Optional client-side settings bag (not sent automatically).
     *
     * @var array
     */
    public $settings = [];

    /**
     * API root URL, always ending with a trailing slash.
     *
     * @var string
     */
    public $root;

    /**
     * Reserved for future debug output.
     *
     * @var bool
     */
    public $debug = false;

    /**
     * Maps API error names to exception class names.
     *
     * @var array<string, string>
     */
    public static $error_map = [
        'Invalid_Key' => 'Eazycv_Invalid_Key',
    ];

    /**
     * @param string               $apikey   API key (X-Authorization)
     * @param string|null          $customer Customer slug (X-Customer)
     * @param string|null          $root     API base URL
     * @param array                $options  Supported keys:
     *                                       - settings (array)
     *                                       - http_client (ClientInterface) for DI / tests
     *
     * @throws Eazycv_Error
     */
    public function __construct($apikey = '', $customer = null, $root = null, $options = [])
    {
        if (!$apikey) {
            throw new Eazycv_Error('You must provide a Eazycv API key');
        }
        if (!$customer) {
            throw new Eazycv_Error('You must provide a Eazycv customer slug');
        }
        if (!$root) {
            $root = 'https://api.eazycv.cloud';
        }

        $this->apiKey = $apikey;
        $this->root = $root;
        $this->customer = $customer;

        if (!empty($options['settings'])) {
            $this->settings = $options['settings'];
        }

        if (!empty($options['http_client']) && $options['http_client'] instanceof ClientInterface) {
            $this->client = $options['http_client'];
        } else {
            $this->client = new Client();
        }

        $this->root = rtrim($this->root, '/') . '/';
    }

    /**
     * Sets the token for the session of a logged in user.
     *
     * @param string $userKey
     *
     * @return void
     */
    public function setUserKey($userKey)
    {
        $this->userKey = $userKey;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Authenticate a back-office user and store the session token as user key.
     *
     * @param string|null $email
     * @param string|null $passWord
     * @param bool       $persistant Keep the intentional API field spelling.
     * @param mixed|null  $captcha
     *
     * @return mixed
     *
     * @throws Eazycv_Error
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loginUser($email = null, $passWord = null, $persistant = false, $captcha = null)
    {
        if (!$email) {
            throw new Eazycv_Error('You must provide a emailaddress');
        }
        if (!$passWord) {
            throw new Eazycv_Error('You must provide a password');
        }

        $data = $this->post('users/login', [
            'email' => $email,
            'password' => $passWord,
            'captcha' => $captcha,
            'persistant' => $persistant,
        ]);

        if (!empty($data['session'])) {
            $this->setUserKey($data['session']['token']);

            return $data['session'];
        }

        throw new Eazycv_Error('Invalid credentials');
    }

    /**
     * Authenticate a candidate and replace the API key with the session token.
     *
     * @param string|null $email
     * @param string|null $passWord
     * @param bool       $persistant Keep the intentional API field spelling.
     * @param mixed|null  $captcha
     *
     * @return mixed
     *
     * @throws Eazycv_Error
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loginCandidate($email = null, $passWord = null, $persistant = false, $captcha = null)
    {
        if (!$email) {
            throw new Eazycv_Error('You must provide a emailaddress');
        }
        if (!$passWord) {
            throw new Eazycv_Error('You must provide a password');
        }

        $data = $this->post('candidates/login', [
            'email' => $email,
            'password' => $passWord,
            'captcha' => $captcha,
            'persistant' => $persistant,
        ]);

        if (!empty($data['session'])) {
            $this->apiKey = $data['session']['token'];

            return $data['session'];
        }

        throw new Eazycv_Error('Invalid credentials');
    }

    /**
     * @param string $endpoint
     * @param array  $params
     *
     * @return array|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($endpoint, $params = [])
    {
        return $this->request('POST', $endpoint, [
            'body' => json_encode($params),
        ]);
    }

    /**
     * GET data from the EazyCV API.
     *
     * @param string $endpoint
     * @param array  $parameters
     *
     * @return array|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($endpoint, $parameters = [])
    {
        return $this->request('GET', $endpoint, [
            'query' => $parameters,
        ]);
    }

    /**
     * @param string $endpoint
     * @param mixed  $params
     *
     * @return array|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function put($endpoint, $params)
    {
        return $this->request('PUT', $endpoint, [
            'body' => json_encode($params),
        ]);
    }

    /**
     * @param string $endpoint
     *
     * @return array|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function del($endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Shared request path used by get/post/put/del.
     *
     * Headers, SSL verify behaviour and JSON decoding match the historical client.
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $requestOptions
     *
     * @return array|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request($method, $endpoint, array $requestOptions = [])
    {
        try {
            $response = $this->client->request(
                $method,
                $this->root . $endpoint,
                array_merge([
                    'headers' => $this->defaultHeaders(),
                    'decode_content' => true,
                    'verify' => false,
                ], $requestOptions)
            );
        } catch (Eazycv_HttpError $error) {
            return [
                'code' => $error->getCode(),
                'message' => $error->getMessage(),
            ];
        }

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Default headers sent on every API call.
     *
     * @return array<string, mixed>
     */
    private function defaultHeaders()
    {
        return [
            'X-User' => $this->userKey,
            'X-Authorization' => $this->apiKey,
            'X-Customer' => $this->customer,
            'X-response-type' => 'json',
            'Content-Type' => 'application/json',
        ];
    }
}
