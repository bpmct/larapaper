<?php

namespace App\Services\Plugin\Parsers;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class XmlResponseParser implements ResponseParser
{
    public function parse(Response $response): ?array
    {
        $contentType = $response->header('Content-Type');

        if (! $contentType || ! str_contains(mb_strtolower($contentType), 'xml')) {
            return null;
        }

        try {
            $xml = $this->simplexml_load_string_strip_namespaces($response->body());
            if ($xml === false) {
                throw new Exception('Invalid XML content');
            }

            return [$xml->getName() => $this->xmlToArray($xml)];
        } catch (Exception $exception) {
            Log::warning('Failed to parse XML response: '.$exception->getMessage());

            return ['error' => 'Failed to parse XML response'];
        }
    }

    private function xmlToArray(SimpleXMLElement $xml): array
    {
        $array = (array) $xml;

        foreach ($array as $key => $value) {
            if ($value instanceof SimpleXMLElement) {
                $array[$key] = $this->xmlToArray($value);
            } elseif (is_array($value)) {
                foreach ($value as $i => $item) {
                    if ($item instanceof SimpleXMLElement) {
                        $value[$i] = $this->xmlToArray($item);
                    }
                }
                $array[$key] = $value;
            }
        }

        return $array;
    }

    function simplexml_load_string_strip_namespaces($xml_response) {
        // LIBXML_NOCDATA converts CDATA sections to plain text nodes so that
        // (array) casts and (string) casts both return the text content correctly.
        // Without this flag, <el><![CDATA[text]]></el> casts to array as
        // [0 => SimpleXMLElement(0)] or [] instead of the expected string.
        $flags = LIBXML_NOCDATA;

        $xml = simplexml_load_string($xml_response, SimpleXMLElement::class, $flags);
        if ($xml === false) {
            return false;
        }

        $namespaces = array_keys($xml->getDocNamespaces(true));
        $namespaces = array_filter($namespaces, function($name) { return !empty($name); });
        if (count($namespaces) == 0) {
            return $xml;
        }
        $namespaces = array_map(function($ns) { return "$ns:"; }, $namespaces);

        $xml_no_namespaces = str_replace(
            array_merge(["xmlns="], $namespaces),
            array_merge(["ns="], array_fill(0, count($namespaces), '')),
            $xml_response
        );
        return simplexml_load_string($xml_no_namespaces, SimpleXMLElement::class, $flags);
    }
}
