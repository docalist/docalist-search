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

use InvalidArgumentException;

/**
 * Gère le numéro de page de résultats à retourner dans la requête.
 */
trait PageTrait
{
    /**
     * Numéro de la page de résultats à retourner (1-based).
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Ce trait ne peut être utilisé que dans une classe qui supporte la méthode getSize().
     *
     * Retourne le nombre de résultats par page (10 par défaut).
     *
     * @return int Un entier >= 0
     */
    abstract public function getSize();

    /**
     * Modifie le numéro de la page de résultats.
     *
     * Les numéros de page commencent à 1.
     *
     * @param int $page
     *
     * @return self
     */
    public function setPage($page)
    {
        $page = (int) $page;
        if ($page < 1) {
            throw new InvalidArgumentException('Incorrect page number');
        }
        $this->page = $page;

        return $this;
    }

    /**
     * Retourne le numéro de la page de résultats (1 par défaut).
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Stocke la page de résultats à retourner dans la requête qui sera envoyée à Elasticsearch.
     *
     * @param array $request Le tableau contenant la requête à modifier.
     */
    protected function buildPageClause(array & $request)
    {
        // On n'ajoute rien si on est sur la page 1 ou si la requête ne retourne aucun hits
        if (1 === $this->page || 0 === $this->getSize()) {
            return;
        }

        // Indique à Elasticsearch le rang de la première réponse à retourner
        $request['from'] = ($this->page - 1) * $this->getSize();
    }
}
