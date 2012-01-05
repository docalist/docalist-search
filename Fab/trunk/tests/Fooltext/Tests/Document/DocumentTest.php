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

use Fooltext\Document\Document;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $data = array
        (
            'ref' => 1,
            'title' => 'test',
            'author' => 'dm',
            'tags'=>array('just','a','test'),
        );

        $doc = new Document($data);
        $this->assertSame($data, $doc->toArray());
        $this->assertSame(count($data), count($doc));
        $this->assertSame(count($data), $doc->count());
        $this->assertSame((string)$doc, "ref: 1\ntitle: test\nauthor: dm\ntags: just¤a¤test\n");

        $data2 = array();
        foreach($doc as $key=>$value) $data2[$key] = $value;
        $this->assertSame($data, $data2);

        foreach($data as $key=>$value)
        {
            $this->assertTrue(isset($doc->$key));
            $this->assertTrue(isset($doc[$key]));

            $this->assertSame($value, $doc->$key);
            $this->assertSame($value, $doc[$key]);
            if (is_string($value))
            {
                $value = strtoupper($value);
                $doc->$key = $value;
                $this->assertSame($value, $doc->$key);
                $this->assertSame($value, $doc[$key]);

                $value = strtolower($value);
                $doc[$key] = $value;
                $this->assertSame($value, $doc->$key);
                $this->assertSame($value, $doc[$key]);
            }
            unset($doc->$key);
            $this->assertFalse(isset($doc->$key));
            $this->assertFalse(isset($doc[$key]));
            $this->assertSame(null, $doc->$key);
            $this->assertSame(null, $doc[$key]);
        }
        $this->assertSame(0, count($doc));
        $this->assertSame(0, $doc->count());

        // ***********

        $doc = new Document($data);

        // Vérifie qu'on peut modifier le document dans une boucle foreach
        // c'est une limitation de ArrayAccess. En fait, le code ci-dessous,
        // malgré le &$value ne fera rien.
        $data2 = (array)$doc;
        foreach($doc as $key => &$value)
        {
            if (is_string($value)) $data2[$key] = $value = strtoupper($value);
        }
        $this->assertSame($data2, $doc->toArray());

        // __get, __set, __isset et __unset ne peuvent jamais être appellées.
        // on les appelle içi juste pour avoir un CC à 100%.
        $doc->__get('a');
        $doc->__set('a','A');
        $doc->__isset('a');
        $doc->__unset('a');


    }
}