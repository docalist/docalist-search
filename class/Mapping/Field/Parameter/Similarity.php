<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Options;

/**
 * Gère le paramètre "similarity" d'un champ de mapping.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/similarity.html
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/index-modules-similarity.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Similarity
{
    /**
     * L'algorithme Okapi BM25 (par défaut).
     *
     * @link https://en.wikipedia.org/wiki/Okapi_BM25
     *
     * @var string
     */
    public const BM25_SIMILARITY = 'BM25';

    /**
     * Recherche booléenne (pas de scoring).
     *
     * @link https://en.wikipedia.org/wiki/Boolean_model_of_information_retrieval
     *
     * @var string
     */
    public const BOOLEAN_SIMILARITY = 'boolean';

    /**
     * Similarité par défaut (définie dans les settings de l'index ou dans la configuration elasticsearch).
     *
     * @link https://en.wikipedia.org/wiki/Boolean_model_of_information_retrieval
     *
     * @var string
     */
    public const DEFAULT_SIMILARITY = '';

    /**
     * Modifie l'algorithme de scoring utilisé par le champ.
     *
     * @param string $similarity
     *
     * @return self
     */
    public function setSimilarity(string $similarity); // pas de return type en attendant covariant-returns

    /**
     * Retourne l'algorithme de scoring utilisé par le champ.
     *
     * @return string
     */
    public function getSimilarity(): string;
}
