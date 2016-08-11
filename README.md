# array2xml
array2xml is a simple library for converting arrays to XML
 
## Why?
There are several libraries for converting arrays to XML, but they all require special syntax for annotating schema 
details for things like what to send array items as, attributes, and namespaces.

I was looking for a solution that would allow consumers of other libraries to provide an array without needing to know
those xml schema details.

## How it works
This library separates the array data from schema data so it is ideal for use in libraries where schema details are 
needed but you don't want to put that on users of your library. When instantiating the ```fillup\A2X``` class 
you pass the data array as the first parameter and optionally provide a second array parameter with schema details.

This library supports serializing associative arrays, normal arrays, adding attributes, and namespaces. 

The schema array is a simple format of an associative array where the key is the path/position in the array and the 
value is an array with schema details. 

### Associative arrays
Associative arrays are the easiest thing to serialize to XML because the format ```['key' => 'value']``` very naturally 
maps to ```<key>value</key>```.

### Non-associative arrays
In PHP we represent normal arrays something like ```['item1', 'item2', 'item3']```, but when serializing to XML 
this is a challenge because each element must be wrapped with a tag. This can be done by using the ```sendItemsAs``` 
element in the schema for a given position. See the example below where the contacts element in the array is an array 
of associative arrays. The scheme defines to send each as ```contact```. 

A2X also recognizes simple forms of plurals, so if the array data element has a name of ```contacts``` and you do not 
specify what it's items should be sent as it will strip the trailing ```s``` and send each as ```contact```.

### Attributes
If you need to use attributes in your xml, like ```<contact type="email"><value>name@domain.com</value></contact>``` you can do so 
by defining the attributes array in the schema for the position in the XML that needs attributes. The values of the 
```attributes``` array in the schema relate to what child elements should be serialized as attributes. This makes it 
very natural in the original array to just say:

```php
[
    'contact' => [
        'type' => 'email',
        'value' => 'name@domain.com',
    ]
]
```

and in the schema provide:

```php
[
    '/path/to/contact' => [
        'attributes' => [
            'type'
        ]
    ]
]
```

See the example below for how ```/person``` and ```/person/contacts/contact``` have attributes.

### Namespaces
If you need to use namespaces in your XML there are two places to define them. First you must provide the actual 
namespace definitions, that is the map from namespace prefix to URI. These are provided in the specal ```@namespaces``` 
element of the schema array. Second, for any given position in the schema array you can specify a ```namespace``` 
attribute with a single string value that should map to one of the prefixes defined in ```@attributes```. See example 
below for how ```ns1``` and ```ns2``` are defined in ```@attributes``` and then used for positions 
```/person/contacts``` and ```/person/contacts/contact```.

If you want to have all elements at and under a specific position to have the same namespace you can use the 
```childNamespace``` attribute in the schema. This will apply the given namespace to all elements below the given 
position. 

### List elements without a parent wrapping tag
If you need to generate a list of elements without a wrapping parent tag, the schema setting for ```includeWrappingTag``` 
may be set to ```false```. By default it is considered ```true``` if not present. This is useful in the following example 
where you have a list of ```children``` but want them each listed as a ```child``` element instead of as 
```<children><child></child><child></child></children>```.

```php
<?php
use fillup\A2X;

$data = [
    'person' => [
        'name' => 'Daddio',
        'children' => [
            [
                'name' => 'Older Brother',
            ],
            [
                'name' => 'Little Sister',
            [
        ]
    ]
];

$schema = [
    '/person/children' => [
        'sendItemsAs' => 'child',
        'includeWrappingTag' => false,
    ],
];

$a2x = new A2X($data, $schema);
$xml = $a2x->asXml();
```

In that example ```$xml``` (if formatted) would be:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<person>
    <name>Daddio</name>
    <child>
        <name>Older Brother</name>
    </child>
    <child>
        <name>Little Sister</name>
    </child>
</person>
```


## Usage

```php
<?php
use fillup\A2X;

$data = [
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
    '@namespaces' => [
        'ns1' => 'http://namespaceone.com',
        'ns2' => 'http://namespacetwo.com',
    ],
];

$a2x = new A2X($data, $schema);
$xml = $a2x->asXml();
```

In the above example, ```$xml``` will contain the string:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<person xmlns:ns1="http://namespaceone.com" xmlns:ns2="http://namespacetwo.com" attributeName="attribute value">
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
    <ns1:contacts>
        <ns2:contact type="email">
            <ns2:value>user@domain.com</ns2:value>
        </ns2:contact>
        <ns2:contact type="mobile">
            <ns2:value>11235551234</ns2:value>
        </ns2:contact>
    </ns1:contacts>
</person>
```

A2X does not currently support pretty printing the xml as is displayed here, but I show it formatted for easier 
reading.

## Contributing
Contributions are welcome as either issues or even better pull requests. If you like this library and use it, let me 
know, I'd love to know if others are benefiting from it as well. phillip dot shipley at gmail.

## License
The MIT License (MIT)

Copyright (c) 2016 Phillip Shipley

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.