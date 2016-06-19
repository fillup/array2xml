<?php
namespace tests;

use fillup\A2X;
use fillup\NotArrayException;

include __DIR__ . '/../vendor/autoload.php';

class A2XTest extends \PHPUnit_Framework_Testcase
{
    public function testBasic()
    {
        $array = [
            'person' => [
                'name' => [
                    'given' => 'first',
                    'surname' => 'last',
                ],
                'address' => [
                    'street1' => '123 Somewhere',
                    'street2' => '',
                    'city' => 'Anytown',
                    'state' => 'AA',
                    'country' => 'USA',
                ],
                'age' => 40,
                'contacts' => [
                    [
                        'type' => 'email',
                        'value' => 'user@domain.com',
                    ],
                    [
                        'type' => 'mobile',
                        'value' => '11235551234',
                    ],
                ],
            ],
        ];

        $schema = [
            '/person/contacts' => [
                'sendItemsAs' => 'contact',
            ],
        ];

        $expectedXml = '<person><name><given>first</given><surname>last</surname></name><address><street1>123 Somewhere</street1><street2></street2><city>Anytown</city><state>AA</state><country>USA</country></address><age>40</age><contacts><contact><type>email</type><value>user@domain.com</value></contact><contact><type>mobile</type><value>11235551234</value></contact></contacts></person>';

        $a2x = new A2X($array, $schema);
        $this->assertEquals($expectedXml, $a2x->asXml());

        $withoutSchema = new A2X($array);
        $this->assertEquals($expectedXml, $a2x->asXml());
    }

    public function testNotArray()
    {
        $this->setExpectedException(NotArrayException::class);
        $a2x = new A2X('string');
    }
}