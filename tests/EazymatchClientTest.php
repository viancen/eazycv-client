<?php
namespace tests\Test;


class EazymatchClientTest extends \PHPUnit_Framework_TestCase
{
    public function testConnect()
    {
        $foo = true;
        //try and connect

        $tt = new \EazymatchClient('d9edf0d5d68c5e9bbf284094dd456fcd74d401d4','http://talentree.io/v1/');

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