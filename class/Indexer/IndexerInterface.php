<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Indexer;

use Docalist\Search\IndexManager;

/**
 * Interface pour les indexeurs.
 *
 * Le rôle d'un indexeur consiste à transformer un contenu quelconque (article, page, notice, profil utilisateur,
 * commentaire, produit...) en document destiné à être indexé par ElasticSearch.
 *
 * Chaque indexeur gère un seul type de contenu et dispose de méthode permettant de convertir un objet en document
 * ElasticSearch, d'indexer / mettre à jour / supprimer un objet, de réindexer la totalité des objets de ce type.
 *
 * Cette classe de base contient également des méthodes qui permettent de mapper de façon homogène entre différents
 * types certains des champs standard de WordPress (post_status, post_title, etc.)
 */
interface IndexerInterface
{
    /**
     * Retourne un code unique permettrant d'identifier les contenus gérés par cet indexeur.
     *
     * @return string
     */
    public function getType();

    /**
     * Retourne le libellé à utiliser pour désigner les contenus gérés par cet indexeur.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Retourne la catégorie dans laquelle figure les contenus gérés par cet indexeur (WordPress, Bases docalist...).
     *
     * @return string
     */
    public function getCategory();

    /**
     * Construit les settings de l'index ElasticSearch.
     *
     * Cette méthode permet à l'indexeur d'ajouter dans les settings de l'index les analyseurs et les mappings
     * dont il a besoin.
     *
     * La création des settings de l'index est un processus distribué entre les différents indexeurs et chaque
     * indexeur doit veiller à ne pas écraser les settings définis préalablement par un autre indexeur.
     *
     * @param array $settings Les settings à mettre à jour.
     *
     * @return array Les settings modifiés.
     */
    public function buildIndexSettings(array $settings);

    /**
     * Permet à l'indexeur d'installer les hooks nécessaires pour permettre l'indexation en temps réel des contenus
     * qu'il gère.
     *
     * @param IndexManager $indexManager Le gestionnaire d'index docalist-search.
     */
    public function activateRealtime(IndexManager $indexManager);

    /**
     * Indexe tous les documents gérés par l'indexeur.
     *
     * @param IndexManager $indexManager Le gestionnaire d'index docalist-search.
     */
    public function indexAll(IndexManager $indexManager);
}
