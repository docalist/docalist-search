<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\SearchRequest;

use Docalist\Search\SearchUrl;

/**
 * Gère l'objet SearchUrl associé à la requête.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
