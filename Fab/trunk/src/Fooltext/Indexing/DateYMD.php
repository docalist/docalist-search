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

class DateYMD implements AnalyzerInterface
{
    /**
     * Version tokenisée des noms de mois, en français, en anglais
     * et en forme longue et abrégée.
     *
     * @var array
     */
    protected $monthes = array
    (
        '01' => array('january', 'janvier', 'janv', 'jan'),
        '02' => array('february', 'fevrier', 'fevr', 'feb', 'fev'),
        '03' => array('mars', 'mar'),
        '04' => array('april', 'avril', 'apr', 'avr'),
        '05' => array('may', 'mai'),
        '06' => array('june', 'juin', 'jun'),
        '07' => array('july', 'juillet', 'jul'),
        '08' => array('august', 'aout', 'aug', 'aou'),
        '09' => array('september', 'septembre', 'sep', 'sept'),
        '10' => array('october', 'octobre', 'oct'),
        '11' => array('november', 'novembre', 'nov'),
        '12' => array('december', 'decembre', 'dec'),
    );

    public function analyze(AnalyzerData $data)
    {
        foreach ($data->content as $value)
        {
            if (strlen($value) < 8) continue;
            $terms = self::$monthes[substr($value, 4, 2)];
            array_unshift($terms, substr($value, 0, 4));
            array_push($terms, substr($value, 6, 2));
            $data->terms[] = $terms;
        }
    }
}