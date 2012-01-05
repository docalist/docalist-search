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

/**
 * Compte le nombre d'articles présents dans le champ.
 *
 * Pour un champ vide, ajoute le mot-clé "__empty"
 * Pour un champ scalaire, ajoute le mot-clé "__has1"
 * Pour un champ multivalué, ajoute un mot-clé de la forme "__hasX" ou x représente le nombre
 * d'articles présents dans le champ.
 */
class Countable implements AnalyzerInterface
{
    public function analyze(AnalyzerData $data)
    {
        // champ vide : __empty
        if (0 === count($data->content))
        {
            $data->keywords[] = '__empty';
        }

        // Au moins un article : __hasN
        else
        {
            $data->keywords[] = '__has' . count($data->content);
        }
    }
}