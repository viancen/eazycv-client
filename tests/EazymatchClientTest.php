<?php
namespace tests\Test;

class EazymatchClientTest extends \PHPUnit_Framework_TestCase
{
    public function testConnect()
    {
        $foo = true;
        //try and connect

        $tt = new EazymatchClient(
            '04c65226-0167-426a-aa24-1089faa6b591',
            'https://api.eazymatch.local',
            'klantnaam'
        );

        if (!empty($tt)) {
            $test = $tt->get('tree');
            if (!empty($test)) {
                $foo = true;
            }
        } else {
            $foo = false;
        }
        $this->assertTrue($foo);

    }

}