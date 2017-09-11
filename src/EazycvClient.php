<?php

use GuzzleHttp\Client;

require_once 'EazycvClient/Exceptions.php';

class EazycvClient
{
    //private client (Guzzle)
    private $client;

    //customer slug
    public $customer;

    //API key, get one at eazycv.nl
    public $apiKey;
    public $userKey = null;
    public $apiSecret;

    //What part of Eazycv is used
    public $settings = [];

    //Root url of API
    public $root;

    //not used yet
    public $debug = false;

    //Todo: map all errors into comprehensible messages
    public static $error_map = [
        "Invalid_Key" => "Eazycv_Invalid_Key",
    ];

    /**
     * EazycvClient constructor.
     *
     * @param null $apiKey
     * @param null $apiSecret
     * @param null $customer
     * @param null $root
     * @param array $options
     */
    public function __construct($apikey = '', $apiSecret = '', $customer = null, $root = null, $options = [])
    {
        if (!$apikey) throw new Eazycv_Error('You must provide a Eazycv API key');
        if (!$apiSecret) throw new Eazycv_Error('You must provide a Eazycv API secret');
        if (!$customer) throw new Eazycv_Error('You must provide a Eazycv customer slug');
        if (!$root) {
            $root = 'https://api.eazycv.net';
        }

        $this->apiKey = md5($apikey . $apiSecret);
        $this->root = $root;
        $this->apiSecret = $apiSecret;
        $this->customer = $customer;

        if (!empty($options)) {
            if (!empty($options['settings'])) {
                $this->settings = $options['settings'];
            }
        }

        $this->client = new Client();

        $this->root = rtrim($this->root, '/') . '/';

    }

    public function setUserKey($userKey)
    {
        $this->userKey = md5($userKey . $this->apiSecret);
    }

    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param null $email
     * @param null $passWord
     * @param null $persistant
     * @throws Eazycv_Error
     * @return array $sessionTokenToReplaceApiTokenWith
     */
    public function loginUser($email = null, $passWord = null, $persistant = false)
    {
        if (!$email) throw new Eazycv_Error('You must provide a emailaddress');
        if (!$passWord) throw new Eazycv_Error('You must provide a password');

        $data = $this->post('users/login', [
            'email' => $email,
            'password' => $passWord,
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
     * @param null $persistant
     * @throws Eazycv_Error
     * @return array $sessionTokenToReplaceApiTokenWith
     */
    public function loginCandidate($email = null, $passWord = null, $persistant = false)
    {
        if (!$email) throw new Eazycv_Error('You must provide a emailaddress');
        if (!$passWord) throw new Eazycv_Error('You must provide a password');

        $data = $this->post('candidates/login', [
            'email' => $email,
            'password' => $passWord,
            'persistant' => $persistant
        ]);

        if (!empty($data['session'])) {
            $this->apiKey = md5($data['session']['token'] . $this->apiSecret);
            return $data['session'];
        } else {
            throw new Eazycv_Error('Invalid credentials');
        }

    }

    /**
     * Post request to Eazycv.io
     *
     * @param $endpoint
     * @param array $params
     * @return mixed
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
     * Post request to Eazycv.io
     *
     * @param $endpoint
     * @return mixed
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
     * Put request to Eazycv.io
     *
     * @param $endpoint
     * @return mixed
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
     * Put request to Eazycv.io
     *
     * @param $endpoint
     * @return mixed
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