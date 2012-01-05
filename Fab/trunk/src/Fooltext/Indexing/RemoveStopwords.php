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
 * Supprime les mots vides présents dans les termes ou dans les postings.
 *
 * Remarque : RemoveStopwords doit être exécuté avant Stemming.
 *
 */
class RemoveStopwords implements AnalyzerInterface
{
    public function analyze(AnalyzerData $data)
    {
        // Si on n'a pas de champ, pas de schéma, donc pas de mots vides
        if (is_null($data->field)) return;

        // Récupère la liste des mots vides
        $stopwords = $data->field->getSchema()->stopwords;

        $data->map(array('terms', 'postings'), function($term) use($stopwords) {
            return isset($stopwords[$term]) ? null : $term;
        });
/*
        // Supprime les mots vides présents dans $data->terms et $data->postings
        foreach(array('terms', 'postings') as $property)
        {
            foreach($data->$property as $i => & $term)
            {
                if (is_array($term))
                {
                    foreach($term as $j => $t)
                    {
                        if (isset($stopwords[$t])) unset($term[$j]);
                    }
                }
                else
                {
                    if (isset($stopwords[$term])) unset($data->$property[$i]);
                }
            }
        }
*/
    }
}