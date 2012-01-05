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
 * Indexation à l'article (mots-clés).
 *
 * Combine ensemble tous les termes pour créer un nouveau mot-clé permettant
 * la recherche par article.
 *
 * Par exemple si le tableau de termes contient array('document', 'papier'),
 * l'analyseur va créer le mot-clé '_document_papier_'.
 *
 * Pour utiliser cet analyseur, vous devez au préalable indexer au mot ou à la phrase.
 */
class Keywords implements AnalyzerInterface
{
    public function analyze(AnalyzerData $data)
    {
        foreach(array('terms', 'postings') as $what)
        {
            foreach($data->$what as $terms)
            {
                $data->keywords[] = '_' . implode('_', (array) $terms) . '_';
            }
        }
    }
}