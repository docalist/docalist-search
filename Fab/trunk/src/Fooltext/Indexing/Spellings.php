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
 * Ajoute les termes dans le correcteur orthographique.
 *
 * Pour utiliser cet analyseur, vous devez au préalable indexer le texte
 * au mot ou à la phrase (i.e. votre chaine d'analyse doit contenir un
 * analyseur tel que {@link \Fooltext\Indexing\Words} ou
 * {@link \Fooltext\Indexing\Phrases}.
 */
class Spellings implements AnalyzerInterface
{
    public function analyze(AnalyzerData $data)
    {
        $data->spellings = array_merge($data->spellings, $data->terms, $data->postings);
    }
}