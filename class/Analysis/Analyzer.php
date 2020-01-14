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

namespace Docalist\Search\Analysis;

use Docalist\Search\Analysis\Component;

/**
 * Un composant d'analyse complet qui peut être appliqué à un champ de mapping.
 *
 * Un analyseur génère les termes de recherche qui seront stockés dans l'index de recherche pour un texte donné.
 * C'est un pipeline constitué de composants d'analyse de plus bas niveau qui applique dans l'ordre :
 *
 * - zéro, un ou plusieurs CharFilter qui préparent le texte à découper (pre-processing),
 * - un Tokenizer (unique et obligatoire) qui génère des termes de recherche,
 * - zéro, un ou plusieurs TokenFilter qui filtrent et transforment les tokens générés (post-processing).
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/analyzer-anatomy.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Analyzer extends Component
{
    /**
     * Retourne le type de l'analyseur.
     *
     * @return string Le nom de l'analyseur elasticsearch de base ('custom', 'standard'...)
     */
    public function getType(): string;

    /**
     * Retourne le nom des filtres de caractères utilisés par l'analyseur.
     *
     * @return string[]
     */
    public function getCharFilters(): array;

    /**
     * Retourne le nom du tokenizer utilisé par l'analyseur.
     *
     * @return string
     */
    public function getTokenizer(): string;

    /**
     * Retourne le nom des filtre de tokens utilisés par l'analyseur.
     *
     * @return string[]
     */
    public function getTokenFilters(): array;
}
