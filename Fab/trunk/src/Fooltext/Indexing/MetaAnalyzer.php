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
 * Classe de base pour les méta-analyseurs.
 *
 * Un méta analyseur se contente d'appeller d'autes analyseurs.
 */
abstract class MetaAnalyzer implements AnalyzerInterface
{
    /**
     * La liste des analyseurs à exécuter.
     *
     * @var array
     */
    protected $analyzers = array();

    /**
     * Constructeur.
     *
     * Initialise les analyseurs qui seront exécutés.
     *
     * @param array $classes les noms de classe des analyseurs à exécuter.
     */
    public function __construct(array $classes)
    {
        foreach($classes as $class)
        {
            $this->analyzers[] = new $class();
        }
    }

    public function analyze(AnalyzerData $data)
    {
        foreach($this->analyzers as $analyzer)
            $analyzer->analyze($data);
    }
}