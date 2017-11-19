<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\SearchRequest;

/**
 * Gère l'équation de recherche (la représentation pour l'utilisateur) de la requête exécutée.
 */
trait EquationTrait
{
    /**
     * Représentation de la requête sous forme d'équation de recherche.
     *
     * @var string
     */
    protected $equation;

    /**
     * Définit la représentation de la recherche en cours sous la forme d'une équation de recherche.
     *
     * @param string $equation
     *
     * @return self
     */
    public function setEquation($equation)
    {
        $this->equation = $equation;

        return $this;
    }

    /**
     * Retourne une représentation de la recherche en cours sous la forme d'une équation de recherche.
     *
     * @return string
     */
    public function getEquation()
    {
        return $this->equation;
    }
}
