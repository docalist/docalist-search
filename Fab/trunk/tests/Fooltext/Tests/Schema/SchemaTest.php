<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Tests
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Tests;

use Fooltext\Schema\Schema;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptySchema()
    {
        $schema = new Schema();

        $this->assertEquals
        (
            $schema->toXml(false),
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<schema><version>2</version></schema>' . "\n"
        );
        // ce serait plus simple avec $this->assertXmlStringEqualsXmlString mais
        // ça permet de vérifier les fins de ligne

        $this->assertSame
        (
            $schema->toJson(),
        	'{"_nodetype":"schema","version":2,"label":"","description":"","stopwords":"","indexstopwords":true,"creation":null,"lastupdate":null,"docid":null,"_lastid":null}'
        );
        $this->assertSame
        (
            $schema->toJson(true),
'{
    "_nodetype": "schema",
    "version": 2,
    "label": "",
    "description": "",
    "stopwords": "",
    "indexstopwords": true,
    "creation": null,
    "lastupdate": null,
    "docid": null,
    "_lastid": null
}'
        );


        $this->assertEquals($schema, $schema->getSchema());
    }

    public function testSettingStopwords()
    {
        $schema = new Schema();
        $schema->stopwords = 'le la les de du des a c en';
        $result = array
        (
            'le' => true,
            'la' => true,
            'les' => true,
            'de' => true,
            'du' => true,
            'des' => true,
            'a' => true,
            'c' => true,
            'en' => true,
        );

        $this->assertSame($schema->stopwords, $result);

        $this->assertXmlStringEqualsXmlString
        (
            $schema->toXml(true),
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <schema>
                <version>2</version>
                <stopwords>
                    <item>le</item>
                    <item>la</item>
                    <item>les</item>
                    <item>de</item>
                    <item>du</item>
                    <item>des</item>
                    <item>a</item>
                    <item>c</item>
                    <item>en</item>
                </stopwords>
            </schema>'
        );

        $schema->stopwords = array_keys($result);

        $this->assertSame
        (
            $schema->stopwords,
            $result
        );
    }
}