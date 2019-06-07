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

namespace Docalist\Search\Mapping\Field\Info;

use InvalidArgumentException;

/**
 * Décrit les constantes utilisées pour les caractéristiques des champs de mapping.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface FeaturesConstants
{
    /**
     * Indique que le champ est utilisable pour faire une recherche full-text.
     *
     * Un champ full-text dispose d'un analyseur qui génère un ou plusieurs tokens à partir du contenu.
     *
     * Dans une requête, un champ full-text recherche les documents qui contiennent un ou plusieurs des
     * termes de recherche indiqués et il contribue au calcul du score des réponses obtenues.
     *
     * Les champs full-text sont listés sur la page docalist "champs par défaut et pondération".
     */
    public const FULLTEXT = 1;

    /**
     * Indique que le champ est utilisable comme filtre de recherche.
     *
     * Un filtre stocke son contenu tel quel dans l'index de recherche, sous la forme d'un token unique.
     *
     * Dans une requête, un filtre recherche les documents qui ont le token exact indiqué. Les filtres ne
     * contribuent pas au calcul du score des réponses, ils se contentent d'exclure des résultats les
     * documents qui ne passent pas le filtre.
     *
     * Par défaut, les filtres sont "inclusifs" : si on a plusieurs valeurs pour un même filtre, celles-ci
     * sont combinées en "ET". Par exemple, une requête qui contient les filtres "keyword:a" et "keyword:b"
     * ne retournera que les documents qui ont les deux mots-clés indiqués. Le flag "EXCLUSIVE" permet de
     * changer ce comportement.
     */
    public const FILTER = 2;

    /**
     * Pour un filtre, indique que les valeurs du filtre sont mutuellement exclusives.
     *
     * Par défaut, un champ FILTER combine les différentes valeurs recherchées en "ET". Pour un champ qui ne
     * peut contenir qu'une seule valeur et pour les champs multivalués dont les valeurs sont mutuellement
     * exclusives, ça n'a pas de sens.
     *
     * Par exemple, pour un champ comme "status", une requête de la forme "status:pending AND status:publish"
     * ne retournera aucun résultat car un document ne peut pas avoir qu'un seul statut de publication.
     *
     * Avec le flag "EXCLUSIVE", les différentes valeurs du filtre seront combinées en "OU" et la requête
     * retournera les documents qui sont soit en statut "pending", soit en statut "publish".
     */
    public const EXCLUSIVE = 4;

    /**
     * Indique que le champ est utilisable dans une agrégation.
     */
    public const AGGREGATE = 8;

    /**
     * Indique que le champ est une clé de tri.
     *
     * Une clé de tri est stockée telle qu'elle dans l'index. Elle peut être utilisée directement pour
     * trier les réponses obtenues ou servir à créer une clé de tri composite.
     *
     * Les clés de tri sont listées sur la page docalist "tris disponibles".
     */
    public const SORT = 16;

    /**
     * Indique que le champ est utilisable pour faire des lookups sur index.
     *
     * Un champ lookup permet d'implémenter un système d'autocomplétion qui fournit toutes les entrées
     * qui commencent par un préfixe donné.
     */
    public const LOOKUP = 32;

    /**
     * Réservé, non implémenté. Indique que le champ est utilisable pour la correction orthographique.
     */
    public const SPELLCHECK = 64;

    /**
     * Réservé, non implémenté. Indique que le champ est utilisable pour la mise en surbrillance des
     * termes de recherche.
     */
    public const HIGHLIGHT = 128;
}
