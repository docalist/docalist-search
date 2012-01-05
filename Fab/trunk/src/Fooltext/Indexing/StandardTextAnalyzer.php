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
 * Analyseur standard pour les champs texte (titre, résumé, etc.)
 *
 * Exécute dans l'ordre les analyseurs suivants :
 * - {@link \Fooltext\Indexing\Lowercase}
 * - {@link \Fooltext\Indexing\Phrase}
 * - {@link \Fooltext\Indexing\Spelling}
 */
class StandardTextAnalyzer extends MetaAnalyzer
{
    public function __construct()
    {
        parent::__construct(array
        (
        	'Fooltext\Indexing\Lowercase',
        	'Fooltext\Indexing\Phrases',
        	'Fooltext\Indexing\Spellings'
        ));
    }
}