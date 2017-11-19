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

use Docalist\Search\SearchUrl;

/**
 * Gère l'objet SearchUrl associé à la requête.
 */
trait SearchUrlTrait
{
    /**
     * L'objet SearchUrl qui a généré cette requête.
     *
     * @var SearchUrl
     */
    protected $searchUrl;

    /**
     * Définit l'objet SearchUrl qui a créé cette requête.
     *
     * @param SearchUrl $searchUrl
     *
     * @return self
     */
    public function setSearchUrl(SearchUrl $searchUrl)
    {
        $this->searchUrl = $searchUrl;

        return $this;
    }

    /**
     * Retourne l'objet SearchUrl qui a créé cette requête.
     *
     * @return SearchUrl Retourne null si setSearchUrl() n'a jamais été appellée.
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }
}
