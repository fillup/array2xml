<?php
namespace tests;

use fillup\A2X;
use fillup\NotArrayException;

include __DIR__ . '/../vendor/autoload.php';

class A2XTest extends \PHPUnit_Framework_Testcase
{
    public function testIt()
    {
        $array = [
            'person' => [
                'attributeName' => 'attribute value',
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
                'children' => [
                    [
                        'name' => 'Big Kiddo'
                    ],
                    [
                        'name' => 'Little Kiddo'
                    ]
                ]
            ],
        ];

        $schema = [
            '/person' => [
                'attributes' => [
                    'attributeName',
                ],
            ],
            '/person/contacts' => [
                'sendItemsAs' => 'contact',
                'namespace' => 'ns1',
                'childNamespace' => 'ns2',
            ],
            '/person/contacts/contact' => [
                'attributes' => [
                    'type',
                ],
            ],
            '/person/children' => [
                'sendItemsAs' => 'child',
                'includeWrappingTag' => false,
            ],
            '@namespaces' => [
                'ns1' => 'http://namespaceone.com',
                'ns2' => 'http://namespacetwo.com',
            ],
        ];

        $expectedXml = '<?xml version="1.0" encoding="UTF-8"?><person xmlns:ns1="http://namespaceone.com" xmlns:ns2="http://namespacetwo.com" attributeName="attribute value"><name><given>first</given><surname>last</surname></name><address><street1>123 Somewhere</street1><street2></street2><city>Anytown</city><state>AA</state><country>USA</country></address><age>40</age><ns1:contacts><ns2:contact type="email"><ns2:value>user@domain.com</ns2:value></ns2:contact><ns2:contact type="mobile"><ns2:value>11235551234</ns2:value></ns2:contact></ns1:contacts><child><name>Big Kiddo</name></child><child><name>Little Kiddo</name></child></person>';
        $a2x = new A2X($array, $schema);
        $this->assertEquals($expectedXml, $a2x->asXml());

        $expectedXml = '<?xml version="1.0" encoding="UTF-8"?><person><attributeName>attribute value</attributeName><name><given>first</given><surname>last</surname></name><address><street1>123 Somewhere</street1><street2></street2><city>Anytown</city><state>AA</state><country>USA</country></address><age>40</age><contacts><contact><type>email</type><value>user@domain.com</value></contact><contact><type>mobile</type><value>11235551234</value></contact></contacts><children><item><name>Big Kiddo</name></item><item><name>Little Kiddo</name></item></children></person>';
        $withoutSchema = new A2X($array);
        $this->assertEquals($expectedXml, $withoutSchema->asXml());
    }

    public function testNotArray()
    {
        $this->setExpectedException(NotArrayException::class);
        $a2x = new A2X('string');
    }
}