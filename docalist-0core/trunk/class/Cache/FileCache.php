<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Cache
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Cache;
use Exception;

/**
 * Un cache permettant de stocker des fichiers (template compilé, version
 * SQLite d'une table d'autorité, etc.)
 */
class FileCache {
	/**
	 * @var int Permissions des fichiers ajoutés au cache.
	 */
    const FILE_MASK = 0660;

	/**
	 * @var int Permissions des répertoires qui sont créés.
	 */
    const DIR_MASK = 0770;

    /**
     * @var string Racine des fichiers ajoutés au cache.
     */
    protected $root;

    /**
     * @var string Path absolu du répertoire contenant les fichiers du cache.
     */
    protected $directory;

    /**
     * Crée un nouveau cache.
     *
     * Pour créer un nouveau cache, vous devez fournir deux noms de répertoire.
     *
     * - Le premier désigne votre "document root". Il doit s'agir d'un
     *   répertoire existant et vous devez fournir le path absolu. Ce path
     *   permet de déterminer le chemin relatif du fichier dans le cache.
     *   Seul les fichiers dont le path commence par ce chemin pourront être
     *   stockés en cache.
     *
     * - Le second désigne l'endroit où seront stockés les fichiers en cache.
     *   Là aussi il doit s'agir d'un path absolu, mais le répertoire sera
     *   créé s'il n'existe pas déjà (assurez-vous d'avoir les droits en
     *   écriture).
     *
     * Important : FileCache ne vérifie pas que vous passez des path corrects,
     * c'est à vous de vérifier que vous utilisez des paths corrects, non
     * relatifs.
     *
     * @param string $root Racine des fichiers qui pourront être stockés
     * dans ce cache.
     *
     * @param string $directory Path du répertoire cache.
     */
    public function __construct($root, $directory) {
        $this->root = rtrim($root,'/\\') . DIRECTORY_SEPARATOR;
        $this->directory = rtrim($directory,'/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Retourne la racine des fichiers stockés par ce cache.
     *
     * @return string
     */
    public function root() {
        return $this->root;
    }

    /**
     * Retourne le répertoire du cache.
     *
     * @return string
     */
    public function directory() {
        return $this->directory;
    }

    /**
     * Retourne le path dans le cache du fichier indiqué.
     *
     * On ne teste pas si le fichier existe : on se contente de déterminer le
     * path qu'aurait le fichier s'il était mis en cache.
     *
     * @param string $file le path du fichier à tester.
     *
     * @return string le path dans le cache de ce fichier.
     *
     * @throws Exception si le fichier indiqué ne peut pas figurer dans le
     * cache.
     */
    public function path($file) {
    	$file = strtr($file, '/\\', DIRECTORY_SEPARATOR);
        if (0 !== strncasecmp($this->root, $file, strlen($this->root))) {
            $msg = __('Le fichier %s ne peut pas être dans le cache', 'docalist-core');
            throw new Exception(sprintf($msg, $file));
        }

        return $this->directory . substr($file, strlen($this->root));
    }

    /**
     * Indique si un fichier figure dans le cache et s'il est à jour.
     *
     * @param string $file le path du fichier à tester.
     *
     * @param timestamp $time date/heure minimale du fichier présent dans le
     * cache pour qu'il soit considéré comme à jour.
     *
     * @return bool true si le fichier est dans le cache et est à jour, false
     * sinon.
     */
    public function has($file, $time = 0) {
        $path = $this->path($file);
        if (! file_exists($path)) {
            return false;
        }

        return ($time === 0) || (filemtime($path) >= $time);

    }

    /**
     * Stocke un fichier dans le cache.
     *
     * @param string $file le path du fichier à stocker.
     *
     * @param string $data le contenu du fichier à stocker.
     *
     * @throws Exception si le fichier ne peut pas être mis en cache.
     */
    public function put($file, $data) {
        $path = $this->path($file);

        // Crée le répertoire du fichier si besoin
        $directory = dirname($path);
        if (! is_dir($directory) && ! @mkdir($directory, self::DIR_MASK, true))
        {
            $msg = __('Impossible de créer le répertoire FileCache %s', 'docalist-core');
            throw new Exception(sprintf($msg, $directory));
        }

        // Stocke le fichier
        file_put_contents($path, $data, LOCK_EX);
        chmod($path, self::FILE_MASK);
    }

    /**
     * Retourne le contenu d'un fichier en cache.
     *
     * @param string $file le path du fichier à charger.
     *
     * @return string|null les données lues ou null si le fichier n'existe
     * pas ou ne peut pas être lu.
     */
    public function get($file) {
        $path = $this->path($file);

        return file_exists($path) ? file_get_contents($path) : null;
    }

    /**
     * Supprime un fichier ou un répertoire du cache.
     *
     * Aucune erreur n'est générée si le fichier indiqué ne figure pas dans
     * le cache.
     *
     * La fonction essaie également de supprimer les répertoires vides.
     *
     * @param string $file le path du fichier ou du répertoire à supprimer
     * (vide = tout le cache).
     */
    public function clear($file = '') {
    	$file === '' && $file = $this->root;
        $path = $this->path($file);

        // Suppression d'un répertoire complet
        if (is_dir($path)) {

            return $this->rmTree($path);
        }

        // Suppression d'un fichier
        if (! @unlink($path)) {

        	return false;
        }

        // Le répertoire est peut-être vide, maintenant, essaie de le supprimer
        $path = dirname($path);
        while (strlen($path) > strlen($this->directory)) {
        	if (! @rmdir($path)) {
        	    return true; // on ne peut pas supprimer le dir, mais le fichier l'a été lui, donc true
        	}
        	$path = dirname($path);
        }

        // Ok
        return true;
    }

    /**
	 * Indique si le path passé en paramètre est un chemin relatif.
	 *
	 * Remarque : aucun test d'existence du path indiqué n'est fait.
	 *
	 * @param string $path le path à tester
     *
	 * @return bool true si path est un chemin relatif, false sinon
	 */
/*
    protected function isRelativePath($path) {
        if (0 === $len = strlen($path)) {
        	return true;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
        	return false;
        }

        if ($len > 2 && ctype_alpha($path[0]) && $path[1] === ':') return false;

        return true;
    }
*/

    /**
     * Supprime un répertoire et son contenu
     *
     * @param string $directory
     */
    protected function rmTree($directory) {
        $files = array_diff(scandir($directory), array('.','..'));
        foreach ($files as $file) {
        	$path = $directory . DIRECTORY_SEPARATOR . $file;
    		if (is_dir($path)) {
    			if (! $this->rmTree($path)) {
    			    return false;
    			}
    		} else {
    		    if (! unlink($path)) {
    		        return false;
    		    }
    		}
        }

        return @rmdir($directory);
    }
}