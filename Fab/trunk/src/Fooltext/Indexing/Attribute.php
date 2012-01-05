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

use Fooltext\Indexing\AnalyzerInterface;

/**
 * Stocke le contenu du champ sous forme d'attribut.
 *
 * Les attributs peuvent être utilisés pour :
 * - le tri des réponses
 * - collapsing
 * - value ranges
 */
class Attribute implements AnalyzerInterface
{
    /**
     * Code ascii du caractère qui sera utilisé comme clé si le champ
     * analysé produit une clé vide ou null.
     *
     * Une valeur haute (0xFF) permet de classer "à la fin" les documents
     * qui n'ont pas la clé. Au contraire, une valeur basse (0x01) les
     * classera en début de liste.
     *
     * @var int
     */
    protected static $missingKey = 0xFF;

    public function analyze(AnalyzerData $data)
    {
        foreach(array('terms', 'postings') as $what)
        {
            if (count($data->$what))
            {
                $key = reset($data->$what);
                if (is_array($key)) $key = implode(' ', $key);
                if (is_null($key) || $key === '') $key = chr(static::$missingKey);
                $data->sortkeys[] = $key;
                return;
            }
        }

        if (count($data->keywords))
        {
            $key = reset($data->keywords);
            if (is_array($key)) $key = reset($key);
            if (is_null($key) || $key === '') $key = chr(static::$missingKey);
            $data->sortkeys[] = strtr(trim($key, '_'), '_', ' ');
            return;
        }

        $data->sortkeys[] = chr(static::$missingKey);
        return;
    }
}