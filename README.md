# array2xml
array2xml is a simple library for converting arrays to XML
 
## Why?
There are several libraries for converting arrays to XML, but they all require special syntax for annotating schema 
deatils for things like what to send array items as, attributes, and namespaces.

I was looking for a solution that would allow consumers of other libraries to provide an array without needing to know
those xml schema details.

## How it works
This library separates the array data from schema data so it is ideal for use in libraries where schema details are 
needed but you don't want to put that on users of your library. When instantiating the ```fillup\A2X``` class 
you pass the data array as the first parameter and optionally provide a second array parameter with schema details.

Currently it only supports defining how to serialize array data types, but can be updated to support namespaces and 
attributes without much effort, I just haven't needed that yet.

The schema array is a simple format of an associative array where the key is the path/position in the array and the 
value is an array with schema details. Currently it only supports an element with the name ```sendItemsAs``` to define 
the wrapping element name for array data types. 

A2X recognizes simple forms of plurals, so if the array data element has a name of ```contacts``` and you do not 
specify what its items should be sent as it will strip the trailing ```s``` and send each as ```contact```.

## Usage

```php
<?php
use fillup\A2X;

$data = [
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

$a2x = new A2X($data, $schema);
$xml = $a2x->asXml();
```

In the above example, ```$xml``` will contain the string:

```xml
<person>
    <name>
        <given>first</given>
        <surname>last</surname>
    </name>
    <address>
        <street1>123 Somewhere</street1>
        <street2></street2>
        <city>Anytown</city>
        <state>AA</state>
        <country>USA</country>
    </address>
    <age>40</age>
    <contacts>
        <contact>
            <type>email</type>
            <value>user@domain.com</value>
        </contact>
        <contact>
            <type>mobile</type>
            <value>11235551234</value>
        </contact>
    </contacts>
</person>
```

A2X does not currently support pretty printing the xml as is displayed here, but I show it formatted for easier 
reading.

## Contributing
Contributions are welcome as either issues or even better pull requests. If you like this library and use it, let me 
know, I'd love to know if others are benefiting from it as well. phillip dot shipley at gmail.