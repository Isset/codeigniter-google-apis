<?php

namespace GoogleAPI\Helper;

/**
 * The XML class can be used to convert a string containing XML data into an array.
 *
 * ## Usage
 *
 *     $xml = new GoogleAPI\Helper\XML::to_array("xml string goes here");
 *
 * @author   Yorick Peterse, Isset Internet Professionals
 * @link     http://yorickpeterse.com/ Website of Yorick Peterse
 * @link     http://isset.nl/ Website of Isset Internet Professionals
 * @license  https://github.com/isset/codeigniter-google-apis/blob/master/license.txt The MIT license
 * @version  0.1
 */
class XML
{
	/**
	 * Converts a string containing an XML response from Google into an associative array.
	 * 
	 * This array has an almost identical format to the XML
	 * that is returned by Google with only the following differences:
	 *
	 * * All keys are snaked_cased and lowercased
	 * * Elements that have attributes will be converted to keys with it's value set to
	 * a key/value array containing all attributes and their values. The node's value is
	 * stored in the key "node_value"
	 * * Elements without attributes will have their values set to plain strings
	 * * Keys with a value of "true" or "false" will be typecased to actual booleans
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access public
	 * @param  string $xml_string The XML data as a string.
	 * @return array
	 */
	public static function to_array($xml_string)
	{
		$xml            = new \XMLReader();
		$response       = array();
		$last_node      = NULL;
		$last_node_name = NULL;

		$xml->xml($xml_string);

		// Loop through all XML nodes
		while ( $xml->read() )
		{
			// We'll only want actual elements
			if ( $xml->nodeType === \XMLReader::ELEMENT )
			{
				// Ignore child elements as they'll be processed in parse_xml_node()
				if ( $xml->depth === 0 )
				{
					$xml_node                    = self::parse_xml_node($xml->expand());
					$response[$xml_node['name']] = $xml_node;
				}
			}
		}

		return $response;
	}

	/**
	 * Processes the current XMLReader node and returns an array containing the name
	 * of the node, all it's attributes and it's value. First argument should be 
	 * an instance of the DOMNode class as returned by XMLReader::expand(), without
	 * this this method can't be used recursively.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access private
	 * @parma  object $dom_node A DOMNode instance as returned by XMLReader::expand()
	 * @return array
	 */
	private static function parse_xml_node($dom_node)
	{
		$xml_node = array();

		// Array used for typecasting strings into actual booleans
		$booleans = array(
			'true'  => TRUE,
			'false' => FALSE
		);

		$node_name = self::get_xml_node_name($dom_node->tagName);

		// Typecast the value into a boolean if the value is set to "true" or "false"
		if ( in_array($dom_node->nodeValue, array_keys($booleans)) )
		{
			$node_value = $booleans[$dom_node->nodeValue];
		}
		else
		{
			$node_value = $dom_node->nodeValue;
		}

		// Attributes will be saved as a sub array
		foreach ( $dom_node->attributes as $attr )
		{
			$attr_name = self::get_xml_node_name($attr->name);
			$xml_node['attributes'][$attr_name] = $attr->value;
		}

		// Parse all child nodes
		foreach ( $dom_node->childNodes as $child )
		{
			// Ignore text nodes (they'll be retrieved using DOMElement::nodeValue() anyway)
			if ( $child instanceof \DOMElement )
			{
				$child      = self::parse_xml_node($child);
				$child_name = $child['name'];

				unset($child['name']);

				// If the value is already there we'll merge it with the new list of children
				if ( isset($xml_node['children'][$child_name]) )
				{
					if ( !isset($xml_node['children'][$child_name][1]) )
					{
						$xml_node['children'][$child_name] = array($xml_node['children'][$child_name]);
					}
					
					$xml_node['children'][$child_name][] = $child;
				}
				else
				{
					$xml_node['children'][$child_name] = $child;
				}
			}
		}

		$xml_node['name']  = $node_name;
		$xml_node['value'] = $node_value;

		return $xml_node;
	}

	/**
	 * Converts a (namespaced) node name to a lowercased and snake_cased version of the
	 * node's name.
	 *
	 * @author Yorick Peterse
	 * @since  0.1
	 * @static
	 * @access private
	 * @param  string $node_name The raw name of the current node.
	 * @return string
	 */
	private static function get_xml_node_name($node_name)
	{
		// Replace hyphens with underscores so they can be used in variables
		$node_name = str_replace('-', '_', $node_name);

		// Bye bye camels
		$node_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $node_name));

		return $node_name;
	}
}
