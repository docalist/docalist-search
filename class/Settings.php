<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;
use Docalist\AbstractSettings;

/**
 * Options de configuration du plugin.
 */
class Settings extends AbstractSettings {
    /**
     * @inheritdoc
     */
    protected $defaults = array(
        // Paramètres généraux
        'general' => array(
            // activer la recherche
            'enabled' => false, ),

        // Paramètres du serveur elasticsearch
        'server' => array(
            // Url du serveur
            'url' => 'http://127.0.0.1:9200/',

            // Nom de l'index ES à créer
            'index' => 'wordpress',

            // Timeout des requêtes
            'timeout' => 10,
        ),

        // Contenus à indexer
        'content' => array(
            'posttypes' => array(),
            'comments' => false,
            'users' => false,
        ),
    );
}
