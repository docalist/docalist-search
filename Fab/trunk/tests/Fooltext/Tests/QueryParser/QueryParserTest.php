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

use Fooltext\QueryParser\Parser;

class QueryParserTest extends \PHPUnit_Framework_TestCase
{
    protected $line = 0;

    protected function nextLine($file)
    {
        for (;;)
        {
            if (feof($file)) return false;

            $equation = trim(fgets($file));
            ++$this->line;
            if (substr($equation, 0, 2) === '//') $equation = '';
            if (strlen($equation)) return $equation;
        }
    }

    protected function runFile($path)
    {
        $file = fopen(__DIR__ . '\\' . $path, 'rt');
        $this->line = 0;

        $parser = new Parser();

        for(;;)
        {
            $input = $this->nextLine($file);
            if ($input === false) break;
            $line = $this->line;
            $equation = $this->nextLine($file);
            $optimised = $this->nextLine($file);
            if ($optimised === '%') $optimised = $equation;

            $query = $parser->parseQuery($input);
            $this->assertSame($equation, $query->__toString(), "$path:$line");
            $query->optimize();
            $this->assertSame($equation, $query->__toString(), "$path:$line");
        }
        fclose($file);
    }

    public function testBase()
    {
        $this->runFile('queries.txt');
    }

    public function testSpecial()
    {
        $this->runFile('queries.txt');
    }

    public function testSilentErrors()
    {
        $this->runFile('errors.txt');
    }

}