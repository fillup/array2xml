<?php
namespace fillup;

class A2X
{
    /**
     * @var string
     */
    public $xml;

    /**
     * A2X constructor.
     * @param array $array
     * @param array $schema
     * @param string $version
     * @param string $encoding
     */
    public function __construct($array, $schema = [], $version = '1.0', $encoding = 'UTF-8')
    {
        $this->xml = sprintf('<?xml version="%s" encoding="%s"?>', $version, $encoding);
        $this->xml .= $this->toXml($array, $schema);
    }

    /**
     * @return string
     */
    public function asXml()
    {
        return $this->xml;
    }

    /**
     * @param array $array
     * @param array $schema
     * @param null|string $position
     * @return string
     * @throws NotArrayException
     */
    public function toXml($array, $schema, $position = '')
    {
        $xml = '';
        /*
         * Throw exception if not an array
         */
        if ( ! is_array($array)) {
            throw new NotArrayException();
        }

        if (self::is_assoc($array)) {
            foreach ($array as $key => $value) {
                /*
                 * Update $position
                 */
                $currentPosition = $position . '/' . $key;

                /*
                 * Check for namespace definitions
                 */
                $namespaceDefsString = '';
                $namespaceDefsArray = [];
                if ($position == '') {
                    $namespaceDefs = $this->getNamespaceDefs($schema);
                    if ($namespaceDefs !== null) {
                        foreach ($namespaceDefs as $prefix => $uri) {
                            $namespaceDefsArray[] = sprintf('xmlns:%s="%s"', $prefix, $uri);
                        }
                        if (count($namespaceDefsArray) > 0) {
                            $namespaceDefsString = ' ' . join(' ', $namespaceDefsArray);
                        }
                    }
                }

                /*
                 * Check for namespace for this position
                 */
                $posNamespace = $this->getPositionNamespace($schema, $currentPosition);
                $posNamespaceString = '';
                if ($posNamespace !== null) {
                    $posNamespaceString = $posNamespace . ':';
                }

                /*
                 * Check for any attributes defined for this position and generate string of them
                 */
                $attributeKeys = $this->getAttributes($schema, $currentPosition);
                $attributeArray = [];
                if ($attributeKeys !== null) {
                    foreach($attributeKeys as $attr) {
                        if (isset($value[$attr])) {
                            // Append to string as attribute
                            $attributeArray[] = sprintf('%s="%s"', $attr, $value[$attr]);
                            // Unset from array so doesn't get serialized as an element
                            unset($value[$attr]);
                        }
                    }
                }
                $attributeString = '';
                if (count($attributeArray) > 0) {
                    $attributeString = ' ' . join(' ', $attributeArray);
                }

                /*
                 * Check if this tag should be included as a wrapper for children,
                 * or just include children
                 */
                $includeWrappingTag = $this->getIncludeWrappingTag($schema, $currentPosition);

                /*
                 * Append string to $xml
                 */
                if ($includeWrappingTag) {
                    $xml .= sprintf('<%s%s%s%s>', $posNamespaceString, $key, $namespaceDefsString, $attributeString);
                }
                $xml .= $this->stringValue($value, $schema, $currentPosition);
                if ($includeWrappingTag) {
                    $xml .= sprintf('</%s%s>', $posNamespaceString, $key);
                }
            }
        } else {
            foreach ($array as $element) {
                $elementName = 'item';
                $sendItemAs = $this->getItemsSendAs($schema, $position);
                /*
                 * Update $currentPosition
                 */
                $currentPosition = $position;
                if ($sendItemAs) {
                    $currentPosition .= '/' . $sendItemAs;
                    $elementName = $sendItemAs;
                }

                /*
                 * Check for namespace for this position
                 */
                $posNamespace = $this->getPositionNamespace($schema, $currentPosition);
                $posNamespaceString = '';
                if ($posNamespace !== null) {
                    $posNamespaceString = $posNamespace . ':';
                }

                /*
                 * Check for any attributes defined for this position and generate string of them
                 */
                $attributeKeys = $this->getAttributes($schema, $currentPosition);
                $attributeArray = [];
                if ($attributeKeys !== null) {
                    foreach($attributeKeys as $attr) {
                        if (isset($element[$attr])) {
                            // Append to string as attribute
                            $attributeArray[] = sprintf('%s="%s"', $attr, $element[$attr]);
                            // Unset from array so doesn't get serialized as an element
                            unset($element[$attr]);
                        }
                    }
                }
                $attributeString = '';
                if (count($attributeArray) > 0) {
                    $attributeString = ' ' . join(' ', $attributeArray);
                }

                /*
                 * Check if this tag should be included as a wrapper for children,
                 * or just include children
                 */
                $includeWrappingTag = $this->getIncludeWrappingTag($schema, $currentPosition);

                /*
                 * Append string to $xml
                 */
                if ($includeWrappingTag) {
                    $xml .= sprintf('<%s%s%s>', $posNamespaceString, $elementName, $attributeString);
                }
                $xml .= $this->stringValue($element, $schema, $currentPosition);
                if ($includeWrappingTag) {
                    $xml .= sprintf('</%s%s>', $posNamespaceString, $elementName);
                }
            }
        }

        return $xml;
    }

    /**
     * @param array $element
     * @return string
     */
    public function stringValue($element, $schema, $position)
    {
        if (is_array($element)) {
            return $this->toXml($element, $schema, $position);
        }

        return $element;
    }

    /**
     * @param array $schema
     * @param string $position
     * @return mixed
     */
    public function getItemsSendAs($schema, $position)
    {
        /*
         * Check schema for sendItemAs
         */
        if (isset($schema[$position]['sendItemsAs'])) {
            return $schema[$position]['sendItemsAs'];
        }

        /*
         * No schema definition, check if word is plural and remove 's'
         */
        $parts = explode('/', $position);
        if (is_array($parts)) {
            $length = count($parts);
            $current = $parts[$length - 1];
            if (substr($current, -1, 1) == 's') {
                return substr($current, 0, strlen($current) - 1);
            }
        }
    }

    /**
     * @param array $schema
     * @param string $position
     * @return null|array
     */
    public function getPositionNamespace($schema, $position)
    {
        /*
         * Check schema for namespace for this position
         */
        if (isset($schema[$position]['namespace']) && is_string($schema[$position]['namespace'])) {
            return $schema[$position]['namespace'];
        }

        /*
         * Check if any parent elements have defined a namespace for all children
         */
        $parts = explode('/', $position);
        if (count($parts) > 0) {
            if ($parts[0] == '') {
                unset($parts[0]);
            }
            $path = '';
            foreach ($parts as $part) {
                $path .= '/' . $part;
                if (isset($schema[$path]['childNamespace']) && is_string($schema[$path]['childNamespace'])) {
                    return $schema[$path]['childNamespace'];
                }
            }
        }

        return null;
    }

    /**
     * @param array $schema
     * @param string $position
     * @return null|array
     */
    public function getAttributes($schema, $position)
    {
        /*
         * Check schema for attributes for given position
         */
        if (isset($schema[$position]['attributes']) && is_array($schema[$position]['attributes'])) {
            return $schema[$position]['attributes'];
        }

        return null;
    }

    /**
     * @param array $schema
     * @return null|array
     */
    public function getNamespaceDefs($schema)
    {
        if (isset($schema['@namespaces']) && is_array($schema['@namespaces'])) {
            return $schema['@namespaces'];
        }

        return null;
    }

    /**
     * @param array $schema
     * @param string $position
     * @return bool
     */
    public function getIncludeWrappingTag($schema, $position)
    {
        /*
         * Check schema to see if this position should not be included as a wrapping tag
         */
        if (isset($schema[$position]['includeWrappingTag'])) {
            return (bool) $schema[$position]['includeWrappingTag'];
        }

        return true;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->xml;
    }


    /**
     * Check if given array is assoc or sequential
     * @param array $array
     * @return bool
     */
    public static function is_assoc(array $array) {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }
}
