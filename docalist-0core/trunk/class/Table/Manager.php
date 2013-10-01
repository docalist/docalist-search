<?php
/**
 * This file is part of a "Docalist Biblio" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Biblio
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Table;

use Exception;

/**
 * Gestionnaire de tables d'autorité.
 *
 */
class Manager {
    /**
     * Path des tables.
     *
     * @var string[] un tableau de la forme nom de la table => path
     */
    protected $path;

    /**
     * Tables ouvertes.
     *
     * @var Table[] un tableau de la forme nom de la table => objet Table
     */
    protected $table;

    /**
     * Initialise le gestionnaire de tables.
     */
    public function __construct() {
        $this->path = $this->table = array();
    }

    /**
     * Déclare une table d'autorité.
     *
     * Vous pouvez soit enregistrer une table unique :
     *
     * <code>
     *     register('dclreftype', __DIR__ . '/../tables/dclreftype.php');
     * </code>
     *
     * Soit déclarer plusieur tables en une seule fois :
     *
     * <code>
     * register(array(
     *     'dclreftype' => __DIR__ . '/../tables/dclreftype.php',
     *     'dclrefgenre' => __DIR__ . '/../tables/dclrefgenre.php',
     *     'dclrefmedia' => __DIR__ . '/../tables/dclrefmedia.php',
     * ));
     * </code>
     *
     * @param string|array $table Nom de la table ou tableau contenant les
     * tables à enregistrer.
     *
     * @param string $path Si $table est une chaine, path de la table à
     * déclarer.
     *
     * @throws Exception Si la table est déjà déclarée.
     */
    public function register($table, $path=null) {
        is_scalar($table) && $table = [$table => $path];
        foreach($table as $name => $path) {
            if (isset($this->path[$name])) {
                $msg = __('La table "%s" existe déjà', 'docalist-core');
                throw new Exception(sprintf($msg, $name));
            }
            $this->path[$name] = $path;
        }
    }

    /**
     * Retourne le path de la table dont le nom est indiqué.
     *
     * @param string $table Le nom de la table recherchée.
     *
     * @return string|null Le path de la table ou null si la table indiquée
     * n'existe pas.
     */
    public function path($table) {
        return isset($this->path[$table]) ? $this->path[$table] : null;
    }

    /**
     * Retourne la table indiquée.
     *
     * @param string $table Nom de la table à retourner.
     *
     * @return TableInterface
     *
     * @throws Exception Si la table indiquée n'a pas été enregistrée.
     */
    public function get($table) {

        // Si la table est déjà ouverte, retourne l'instance en cours
        if (isset($this->table[$table])) {
            return $this->table[$table];
        }

        // Vérifie que la table demandée a été enregistrée
        if (! isset($this->path[$table])) {
            $msg = __('La table "%s" n\'existe pas', 'docalist-core');
            throw new Exception(sprintf($msg, $table));
        }

        // Ouvre la table
        $path = $this->path[$table];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        switch($extension) {
            case 'sqlite':
            case 'db':
                return $this->table[$table] = new SQLite($path);

            case 'csv':
            case 'txt':
                return $this->table[$table] = new CsvTable($path);

            case 'php':
                return $this->table[$table] = new PhpTable($path);

            default:
                throw new Exception("Type de table non géré : '.$extension'");
        }
    }

}