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

class Integer implements AnalyzerInterface
{
    public function analyze(AnalyzerData $data)
    {
        foreach ($data->content as $value)
        {
            $data->terms[] = number_format ((int) $value, 0 , 'nu', '');
        }
    }
}