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
 * Interface pour les analyseurs.
 *
 */
interface AnalyzerInterface
{
    /**
     * Analyse le contenu du champ.
     *
     * @param \Fooltext\Indexing\AnalyzerData $data structure contenant
     * le champ à analyser et dans laquelle les analyseurs stockent les
     * termes qu'ils produisent.
     */
    public function analyze(AnalyzerData $data);
}