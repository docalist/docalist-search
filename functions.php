<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Search
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */

// namespace Docalist\Search;

// Liste des objets exposés publiquement
use Docalist\Search\SearchRequest;
use Docalist\Search\SearchResults;

/**
 * Principe :
 * ----------
 *
 * L'idée serait que chaque filtre ou action défini par le plugin
 * soit repris ici sous la forme d'une fonction (globale).
 *
 *
 * Avantages :
 * -----------
 *
 * - Le fichier ainsi obtenu constitue l'API publique du plugin.
 *
 * - Les clauses "use" utilisée pour importer les objets manipulés par les
 *   fonctions permettent d'avoir la liste des objets qu'on expose
 *   publiquement.
 *
 * - Chaque fonction est documentée via phpdoc (paramètres, valeur de retour,
 *   etc.). Comme chaque fonction est identique au hook qu'elle représente, on
 *   a ainsi une documentation exhaustive de tous les hooks du plugin. La page
 *   html qu'on peut générer en automatique (e.g. apigen) constitue la doc de
 *   référence.
 *
 * - Les fonctions sont utiles (i.e. elles ne sont pas là que pour faire la
 *   doc). Moins de risque d'oubli d'un paramètre et c'est tout de même plus
 *   facile d'appeller :
 *   <code>docalist_search_get_rank(12)()</code>
 *   (avec completion et phpdoc dans l'IDE) que de taper :
 *   <code>return apply_filters('docalist_search_get_rank', $id);
 *
 * - Si dans notre propre code, on s'oblige à les utiliser, on est sur que
 *   les signatures et la doc restent à jour.
 *
 * Inconvénients :
 * ---------------
 *
 * - On définit un paquet de fonctions globales
 *      - oui mais elles sont toutes préfixées correctement
 *      - on pourrait les mettre dans le namespace si on voulait
 *
 * - On ne peut pas décrire/documenter un hook dont le nom varie. Par exemple,
 *   comment faire pour "docalist_search_get_{$type}_settings" ?
 *      - ne pas utiliser des hooks comme ça ?
 *      - ne pas documenter ?
 *      - faire une fonction bidon du style docalist_search_get_TYPE_settings()
 *        (juste pour la doc)
 *      - bref, rien de satisfaisant...
 *
 * - Comme ce sont des fonctions globales, on est obligé de les appeller sous
 *   la forme \docalist_search_xxx() si on utilise un namespace.
 *      - par contre, dans les thèmes pas besoin
 *          - sauf s'ils utilisent un namespace...
 *
 * Conventions de nommage des hooks :
 * ----------------------------------
 *
 * - Tous préfixés par le nom du plugin, mais avec un underscore à la place du
 *   tiret (par exemple "docalist_search".
 * - get pour les filtres, verbe pour les actions : docalist_search_get_results,
 *   docalist_search_do_something
 * - before et after pour les actions qui signalent quelque chose :
 *   docalist_search_before_reindex, docalist_search_after_reindex
 *
 *
 * Remarques :
 * -----------
 *
 * - La convention de nommage permet de retrouver facilement tous les hooks.
 *   Par exemple si on recherche "docalist_search", ce n'est utilisé par rien
 *   d'autres que les hook (le text-domain utilise un tiret).
 */

/**
 * Retourne la requête en cours.
 *
 * @return SearchRequest|null
 *
 * @See Searcher::__construct()
 */
function docalist_search_get_request() {
    return apply_filters(__FUNCTION__, null);
}

/**
 * Retourne les résultats de la requête en cours.
 *
 * @return SearchResults|null l'objet Results ou null si on n'a pas de requête
 * en cours.
 *
 * @See Searcher::__construct()
 */
function docalist_search_get_results() {
    return apply_filters(__FUNCTION__, null);
}

/**
 * Retourne le rank d'un hit, c'est à dire la position de ce hit (1-based)
 * dans l'ensemble des réponses qui répondent à la requête.
 *
 * @param int $id
 *
 * @return int Retourne la position du hit dans les résultats (le premier
 * est à la position 1) ou zéro si l'id indiqué ne figure pas dans la liste
 * des réponses.
 *
 * @See Searcher::__construct()
 */
function docalist_search_get_rank($id) {
    return apply_filters(__FUNCTION__, $id);
}

/**
 * Retourne le lien à utiliser pour afficher le hit indiqué tout seul sur
 * une page (i.e. recherche en format long).
 *
 * Le lien retourné est un lien qui permet de relancer une recherche avec
 * start=rank(id) et size=1
 *
 * @param int $id
 *
 * @return string Le lien généré.
 *
 * @See Searcher::__construct()
 */
function docalist_search_get_hit_link($id) {
    return apply_filters(__FUNCTION__, $id);
}
