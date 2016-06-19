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
        $this->xml = $this->toXml($array, $schema, null);
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
                $xml .= sprintf('<%s>', $key);
                $xml .= $this->stringValue($value, $schema, $position . '/' . $key);
                $xml .= sprintf('</%s>', $key);
            }
        } else {
            foreach ($array as $element) {
                $openTag = $closeTag = '';
                $sendItemAs = $this->getItemsSendAs($schema, $position);
                if ($sendItemAs) {
                    $openTag = sprintf('<%s>', $sendItemAs);
                    $closeTag = sprintf('</%s>', $sendItemAs);
                }
                $xml .= $openTag . $this->stringValue($element, $schema, $position) . $closeTag;
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
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
}