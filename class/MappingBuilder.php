<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search;

use InvalidArgumentException;

/**
 * API publique d'un Mapping Builder.
 *
 * Un mapping builder permet de définir comment des données seront indexées dans un moteur de recherche (mapping).
 *
 * Exemple :
 * <code>
 *     $mapper->field('ID')->integer();
 *     $mapper->field('status')->text()->filter();
 *     $mapper->field('title')->text();
 *     $mapper->field('content')->text();
 *     $mapper->field('taxonomy')->text()->filter()->suggest();
 *     $mapper->template('taxonomy.*')->copyFrom('taxonomy')->copyDataTo('topic');
 * </code>
 *
 * Le mapping généré peut être obtenu avec <code>$mapper->getMapping()</code> qui retourne un tableau
 * contenant les paramètres générés.
 */
interface MappingBuilder
{
    /**
     * Retourne le nom de l'analyseur par défaut utilisé pour les champs de type texte.
     *
     * L'analyseur par défaut est utilisé lorsque la méthode {@link text()} est appellée sans paramètres.
     *
     * @return string Le nom de l'analyseur par défaut ('text', 'fr-text', 'en-text'...)
     */
    public  function getDefaultAnalyzer();

    /**
     * Définit l'analyseur par défaut utilisé pour les champs de type texte.
     *
     * L'analyseur par défaut est utilisé lorsque la méthode {@link text()} est appellée sans paramètres.
     *
     * @param string $defaultAnalyzer Le nom de l'analyseur par défaut à utiliser ('text', 'fr-text', 'en-text'...)
     *
     * @return self
     */
    public function setDefaultAnalyzer($defaultAnalyzer);

    /**
     * Crée un nouveau champ dans le mapping.
     *
     * Toutes les méthodes qui seront appellées ensuite (text, filter...) s'appliqueront au champ créé.
     *
     * @param string $name Le nom du champ à ajouter.
     *
     * @throws InvalidArgumentException S'il existe déjà un champ avec le nom indiqué.
     *
     * @return self
     */
    public function addField($name);

    /**
     * Crée un template dans le mapping.
     *
     * Toutes les méthodes qui seront appellées ensuite (text, filter...) s'appliqueront au template créé.
     *
     * @param string $match Le masque indiquant le nom des champs auxquels le
     * nouveau template sera appliqué.
     *
     * @throws InvalidArgumentException S'il existe déjà un template avec le nom indiqué.
     *
     * @return self
     */
    public function addTemplate($match);

    /**
     * Indique que le champ contient du texte littéral (une chaine mais pas du texte dans une langue donnée).
     *
     * Lors de l'indexation, aucun traitement particulier ne sera fait sur les données, elles seront
     * stockées "telles quelles" dans le moteur de recherche (pas de stemming).
     *
     * @return self
     */
    public function literal();

    /**
     * Indique que le champ est de type texte (contenant des mots et des phrases dans une langue donnée).
     *
     * Lors de l'indexation, des traitements seront effectués sur les données (découpage en mots,
     * minusculisation, conversion en termes de recherches...) en fonction de l'analyseur utilisé.
     *
     * @param string $analyzer Optionnel, indique le nom de l'analyseur à utiliser. Si aucun
     * analyseur n'est indiqué, un analyseur générique sera utilisé.
     *
     * @throws InvalidArgumentException Si l'analyseur indiqué n'est pas valide.
     *
     * @return self
     */
    public function text($analyzer = null);

    /**
     * Indique que le champ est de type "entier".
     *
     * @param string $type Optionnel, précision sur le type d'entier (long, integer, short ou byte).
     *
     * @return self
     */
    public function integer($type = 'long');

    /**
     * Indique que le champ est de type "décimal".
     *
     * @param string $type Optionnel, précision sur le type (double ou float).
     *
     * @return self
     */
    public function decimal($type = 'float');

    /**
     * Indique que le champ est de type date.
     *
     * @return self
     */
    public function date();

    /**
     * Indique que le champ est de type "date/heure".
     *
     * @return self
     */
    public function dateTime();

    /**
     * Indique que le champ est de type "booléen".
     *
     * @return self
     */
    public function boolean();

    /**
     * Indique que le champ est de type "binaire".
     *
     * @return self
     */
    public function binary();

    /**
     * Indique que le champ est de type "adresse IP v4".
     *
     * @return self
     */
    public function ipv4();

    /**
     * Indique que le champ est de type "point de géo-localisation" (latitude / longitude).
     *
     * @return self
     */
    public function geoPoint();

    /**
     * Indique que le champ est de type "url".
     *
     * @return self
     */
    public function url();

    /**
     * Indique que le champ peut être utilisé comme filtre (recherche exacte, booléenne).
     *
     * @return self
     */
    public function filter();

    /**
     * Indique que le champ peut être utiliser pour de l'autocomplétion.
     *
     * @return self
     */
    public function suggest();

    /**
     * Indique que les données du champ seront également recopiées dans un autre champ.
     *
     * @param string $field
     *
     * @return self
     */
    public function copyDataTo($field);

    /**
     * Initialise les paramètres du champ avec ceux qui figurent dans le champ indiqué.
     *
     * @param string $field Le nom du champ à recopier.
     *
     * @return self
     *
     * @throws InvalidArgumentException Si le champ indiqué n'existe pas.
     */
    public function copyFrom($field);

    /**
     * Retourne le mapping obtenu.
     *
     * @return array Un tableau contenant les paramètres de topus les champs qui ont été définis.
     */
    public function getMapping();

    /**
     * Réinitialise le builder pour générer un nouveau mapping.
     *
     * @return self
     */
    public function reset();
}
