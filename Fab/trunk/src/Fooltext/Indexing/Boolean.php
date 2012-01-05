<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Indexing
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Indexing;

class Boolean implements AnalyzerInterface
{
    /**
     * Termes à générer si le booléen est à true.
     *
     * @var mixed
     */
    protected static $true = 'true';

    /**
     * Termes à générer si le booléen est à false.
     *
     * @var mixed
     */
    protected static $false = 'false';

    public function analyze(AnalyzerData $data)
    {
        foreach ($data->content as $value)
        {
            $data->keywords[] = $value ? static::$true : static::$false;
        }
    }
}