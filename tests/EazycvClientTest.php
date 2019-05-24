<?php
// run  ./vendor/bin/phpunit tests/EazycvClientTest
namespace tests\Test;

class theTest extends \PHPUnit_Framework_TestCase
{
    public function testConnect()
    {

        $foo = true;
        //try and connect

        $key = '04c65226-0167-426a-aa24-1089faa6b591';
        $secret = '1bb5012d-67e3-4c06-bb14-a8ce4fad8ed4';

        $tt = new \EazycvClient(
            $key,
            $secret,
            'klantnaam',
            'https://api.eazycv.cloud'
        );

        if (!empty($tt)) {
            $test = $tt->loginUser('nieuwenhuizen@gmail.com', 'test');
        } else {
            $this->assertTrue(false);
        }
        //session token is now set. in production = store this in local storage for future requests
        $data = $tt->get('users');

        if (!empty($data)) {
            print_r($data);
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

    }

}