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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Options;

/**
 * Gère le paramètre "search_analyzer" d'un champ de mapping.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-analyzer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface SearchAnalyzer
{
    /**
     * Modifie le nom de l'analyzer utilisé par le champ lors des recherches.
     *
     * @param string $searchAnalyzer
     *
     * @return self
     */
    public function setSearchAnalyzer(string $searchAnalyzer); // pas de return type en attendant covariant-returns

    /**
     * Retourne le nom de l'analyzer utilisé par le champ lors des recherches.
     *
     * @return string
     */
    public function getSearchAnalyzer(): string;
}
