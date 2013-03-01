<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012,2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id: Button.php 396 2013-02-11 12:09:16Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist\Forms;
use Exception, Countable, IteratorAggregate, ArrayIterator;

/**
 * Une collection de fichiers css et javascript.
 */
class Assets implements Countable, IteratorAggregate {
    /**
     * Un asset de type feuille de styles CSS.
     */
    const CSS = 0;

    /**
     * Un asset de type fichier javascript.
     */
    const JS = 1;

    /**
     * @var array Liste des types d'assets valides.
     */
    static protected $types = array(
        self::CSS => self::CSS,
        'css' => self::CSS,
        self::JS => self::JS,
        'js' => self::JS,
    );

    /**
     * Asset inséré en haut de page (head).
     */
    const TOP = 0;

    /**
     * Asset inséré en bas de page (à la fin de body).
     */
    const BOTTOM = 1;

    /**
     * @var array Liste des positions valides (sans jeu de mots...)
     */
    static protected $positions = array(
        self::TOP => self::TOP,
        'top' => self::TOP,
        self::BOTTOM => self::BOTTOM,
        'bottom' => self::BOTTOM,
    );

    /**
     * @var array Liste des propriétés autorisées et valeurs par défaut
     */
    static protected $defaults = array(
        self::CSS => array(
            'type' => self::CSS,
            'name' => null,
            'src' => null,
            'version' => null,
            'position' => self::TOP,
            'media' => 'all',
            'condition' => null,
        ),
        self::JS => array(
            'type' => self::JS,
            'name' => null,
            'src' => null,
            'version' => null,
            'position' => self::BOTTOM,
            'condition' => null,
        )
    );

    /**
     * @var array Liste des assets présents dans la collection.
     */
    protected $assets = array();

    /**
     * Crée une nouvelle liste d'assets.
     *
     * @param array $assets Une liste d'assets à ajouter à la collection.
     */
    public function __construct(array $assets = null) {
        $assets && $this->add($assets);
    }

    /**
     * Ajoute des assets à la liste
     *
     * @param array $assets Les assets à ajouter à la collection.
     *
     * Remarque : $assets est une liste d'assets, donc un tableau de tableaux.
     * Si vous insérez un asset unique, vous devez le wrapper dans un tableau.
     * i.e. :
     * <code>add(array(array('type'=>'css', etc.)))</code>
     * et non pas
     * <code>add(array('type'=>'css', etc.))</code>
     *
     * @return Assets $this
     */
    public function add(array $assets) {
        foreach ($assets as $asset) {
            // Type par défaut : JS
            $type = isset($asset['type']) ? $asset['type'] : self::JS;

            // Vérifie le type d'asset
            if (! isset(self::$types[$type])) {
                throw new Exception('Invalid asset type: ' . $type);
            }
            $type = $asset['type'] = self::$types[$type];

            // On doit avoir soit name, soit src
            if (! isset($asset['name']) && !isset($asset['src'])) {
                throw new Exception('Invalid asset, name or src required');
            }

            if ($bad = array_diff_key($asset, self::$defaults[$type])) {
                $bad = implode(', ', array_keys($bad));
                throw new Exception('Invalid asset properties: ' . $bad);
            }

            // Initialise les propriétés à leur valeur par défaut
            $asset += self::$defaults[$type];

            // Vérifie la position de l'asset
            if (! isset(self::$positions[$asset['position']])) {
                throw new Exception('Invalid asset position: ' . $asset['position']);
            }
            $asset['position'] = self::$positions[$asset['position']];

            // Stocke l'asset
            $key = $asset['name'] ? : $asset['src'];
            $this->assets[$key] = $asset;
        }

        return $this;
    }

    /**
     * Retourne tout ou partie des assets présents dans la collection.
     *
     * @param int|string|null Le type d'asset à retourner (null = tous).
     * @param int|string|null Ne retourner que les assets ayant la position
     * indiquée.
     *
     * @return array un tableau contenant les assets correspondant aux critères
     * indiqués.
     */
    public function get($type = null, $position = null) {
        // Ca ssimple : retourner tout
        if (is_null($type) && is_null($position)) {
            return $this->assets;
        }
echo "here";
        // Vérifie le type demandé
        if ($type) {
            if (! isset(self::$types[$type])) {
                throw new Exception('Invalid asset type: ' . $type);
            }
            $type = self::$types[$type];
        }

        // Vérifie la position demandée
        if ($position) {
            if (! isset(self::$positions[$position])) {
                throw new Exception('Invalid asset position: ' . $position);
            }
            $position = self::$positions[$position];
        }

        // Filtre les assets
        $assets = $this->assets;
        foreach($assets as $key => $asset) {
            if (!is_null($type) && $type !== $asset['type']) {
                unset($assets[$key]);
                continue;
            }
            if (!is_null($position) && $position !== $asset['position']) {
                unset($assets[$key]);
                continue;
            }
        }

        return $assets;
    }

    /**
     * Interface Countable
     *
     * @inheritdoc
     *
     * @return int
     */
    public function count() {
        return count($this->assets);
    }

    /**
     * Interface IteratorAggregate
     *
     * @inheritdoc
     *
     * @return Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->assets);
    }

}
