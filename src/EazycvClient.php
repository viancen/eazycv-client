<?php

use GuzzleHttp\Client;

require_once 'EazycvClient/Exceptions.php';

/**
 *
 * Class EazycvClient
 *
 */
class EazycvClient
{
    //private client (Guzzle)
    private $client;

    //customer slug
    public $customer;

    //API credentials
    public $apiKey;

    //userKey
    public $userKey = null;

    //What part of Eazycv is used
    public $settings = [];

    //Root url of API
    public $root;

    //not used yet
    public $debug = false;

    /**
     * @var string[]
     */
    public static $error_map = [
        "Invalid_Key" => "Eazycv_Invalid_Key",
    ];

    /**
     * EazycvClient constructor.
     * @param string $apikey
     * @param null $customer
     * @param null $root
     * @param array $options
     * @throws Eazycv_Error
     */
    public function __construct($apikey = '', $customer = null, $root = null, $options = [])
    {
        if (!$apikey) {
            throw new \Eazycv_Error('You must provide a Eazycv API key');
        }
        if (!$customer) {
            throw new \Eazycv_Error('You must provide a Eazycv customer slug');
        }
        if (!$root) {
            $root = 'https://api.eazycv.cloud';
        }

        $this->apiKey = $apikey;
        $this->root = $root;
        $this->customer = $customer;

        if (!empty($options)) {
            if (!empty($options['settings'])) {
                $this->settings = $options['settings'];
            }
        }

        $this->client = new Client();
        $this->root = rtrim($this->root, '/') . '/';

    }


    /**
     * Sets the token for the session of a logged in user
     *
     * @param $userKey
     */
    public function setUserKey($userKey)
    {
        $this->userKey = $userKey;
    }

    /**
     * @return array|mixed
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param null $email
     * @param null $passWord
     * @param bool $persistant
     * @param null $captcha
     * @return mixed
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
            'persistant' => $persistant
        ]);

        if (!empty($data['session'])) {
            $this->setUserKey($data['session']['token']);

            return $data['session'];
        } else {
            throw new Eazycv_Error('Invalid credentials');
        }

    }

    /**
     * @param null $email
     * @param null $passWord
     * @param bool $persistant
     * @param null $captcha
     * @return mixed
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
            'persistant' => $persistant
        ]);

        if (!empty($data['session'])) {
            $this->apiKey = $data['session']['token'];

            return $data['session'];
        } else {
            throw new Eazycv_Error('Invalid credentials');
        }

    }

    /**
     * @param $endpoint
     * @param array $params
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($endpoint, $params = [])
    {

        try {

            $response = $this->client->request('POST', $this->root . $endpoint, [
                'headers' => [
                    'X-User' => $this->userKey,
                    'X-Authorization' => $this->apiKey,
                    'X-Customer' => $this->customer,
                    'X-response-type' => 'json',
                    'Content-Type' => 'application/json',
                ],
                'decode_content' => true,
                'verify' => false,
                'body' => json_encode($params)
            ]);

        } catch (Eazycv_HttpError $error) {
            return [
                'code' => $error->getCode(),
                'message' => $error->getMessage()
            ];
        }

        $body = json_decode($response->getBody(), true);

        return $body;
    }

    /**
     * GET eazy data
     *
     * @param $endpoint
     * @param array $parameters
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($endpoint, $parameters = [])
    {

        try {
            $response = $this->client->request('GET', $this->root . $endpoint, [
                'headers' => [
                    'X-User' => $this->userKey,
                    'X-Authorization' => $this->apiKey,
                    'X-Customer' => $this->customer,
                    'X-response-type' => 'json',
                    'Content-Type' => 'application/json',
                ],
                'decode_content' => true,
                'verify' => false,
                'query' => $parameters
            ]);

        } catch (Eazycv_HttpError $error) {

            return [
                'code' => $error->getCode(),
                'message' => $error->getMessage()
            ];
        }

        $body = json_decode($response->getBody(), true);

        return $body;
    }

    /**
     * @param $endpoint
     * @param $params
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function put($endpoint, $params)
    {

        try {
            $response = $this->client->request('PUT', $this->root . $endpoint, [
                'headers' => [
                    'X-User' => $this->userKey,
                    'X-Authorization' => $this->apiKey,
                    'X-Customer' => $this->customer,
                    'X-response-type' => 'json',
                    'Content-Type' => 'application/json',
                ],
                'decode_content' => true,
                'verify' => false,
                'body' => json_encode($params)
            ]);

        } catch (Eazycv_HttpError $error) {
            return [
                'code' => $error->getCode(),
                'message' => $error->getMessage()
            ];
        }

        $body = json_decode($response->getBody(), true);

        return $body;
    }

    /**
     * @param $endpoint
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function del($endpoint)
    {

        try {
            $response = $this->client->request('DELETE', $this->root . $endpoint, [
                'headers' => [
                    'X-User' => $this->userKey,
                    'X-Authorization' => $this->apiKey,
                    'X-Customer' => $this->customer,
                    'X-response-type' => 'json',
                    'Content-Type' => 'application/json',
                ],
                'decode_content' => true,
                'verify' => false
            ]);

        } catch (Eazycv_HttpError $error) {
            return [
                'code' => $error->getCode(),
                'message' => $error->getMessage()
            ];
        }

        $body = json_decode($response->getBody(), true);

        return $body;
    }

}