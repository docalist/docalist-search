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

namespace Docalist\Search;

use Docalist\Search\IndexManager;
use Docalist\Search\Mapping;

/**
 * Interface d'un indexeur.
 *
 * Le rôle d'un indexeur consiste à ajouter, modifier et supprimer de l'index docalist-search
 * les attributs de recherche générés par un contenu Indexable.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Indexer
{
    /**
     * Retourne un code unique permettrant d'identifier les contenus gérés par cet indexeur.
     *
     * Pour des custom post types, le code correspond au champ 'post_type' des contenus indexés.
     *
     * Exemples : "post" et "page" pour les contenus WordPress, "dbprisme" ou "dbstructures" pour
     * des bases docalist, "joboffer" pour un custom post type "offre d'emploi", etc.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Retourne le code à utiliser pour désigner une collection de contenus de ce type.
     *
     * Correspond au contenu du champ "in" utilisé dans les recherches. C'est souvent une variante
     * de ce que retourne getType().
     *
     * Exemples : "posts" et "pages" (avec un "s") pour les contenus WordPress, "prisme" ou
     * "structures" (sans le "db") pour des bases docalist, "jobs" pour des offres d'emploi, etc.
     */
    public function getCollection(): string;

    /**
     * Retourne le libellé à utiliser pour désigner les contenus gérés par cet indexeur.
     *
     * Ce libellé est utilisé dans le back-office docalist-search pour permettre à l'utilisateur de
     * choisir les contenus à indexer.
     *
     * Exemples : "Blog du site", "Pages WordPress", "Prisme", "Structures", "Offres d'emploi", etc.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Retourne un libellé permettant de regrouper les différents indexeurs disponibles par catégorie.
     *
     * Cette catégorie est utilisée dans le back-office docalist-search pour faciliter le choix des
     * contenus à indexer.
     *
     * Exemples : "Contenus WordPress", "Bases Docalist", "Custom Post Types", "Autres contenus", etc.
     *
     * @return string
     */
    public function getCategory(): string;

    /**
     * Retourne le mapping à utiliser pour indexer les contenus gérés par l'indexeur.
     *
     * @return Mapping
     */
    public function getMapping(): Mapping;

    /**
     * Indexe ou réindexe la totalité des contenus gérés par l'indexeur.
     *
     * @param IndexManager $indexManager Le gestionnaire d'index docalist-search.
     */
    public function indexAll(IndexManager $indexManager): void;

    /**
     * Active la réindexation en temps réel des contenus gérés par l'indexeur.
     *
     * Permet à l'indexeur d'installer les hooks nécessaires pour mettre à jour l'index docalist-search
     * lorsque les contenus qu'il gère sont ajoutés, modifiés ou supprimés.
     *
     * @param IndexManager $indexManager Le gestionnaire d'index docalist-search.
     */
    public function activateRealtime(IndexManager $indexManager): void;

    /**
     * Retourne le filtre à appliquer pour lancer une recherche portant sur les contenus de l'indexeur.
     *
     * Cette méthode permet de restreindre la recherche aux contenus gérés par cet indexeur et aux contenus
     * auxquels l'utilisateur a accès :
     *
     * - Le filtre généré *doit* contenir une clause permettant de filtrer les contenus gérés par cet
     *   indexeur (par exemple "in:posts")
     * - Le filtre généré  *peut*  contenir d'autres clauses portant sur le statut des contenus
     *   (par exemple "status:public"), l'auteur du contenu (par exemple "createdby:login"), ou d'autres
     *   critères.
     *
     * @return array Un filtre contenant les différentes clauses à appliquer.
     */
    public function getSearchFilter(): array;
}
