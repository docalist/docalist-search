<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Table;

use Docalist\Cache\FileCache;
use Docalist\Tokenizer;
use PDO;

/**
 * Une table au format PHP.
 *
 */
class PhpTable extends SQLite {
    protected $readonly = true;

    protected function compile()
    {
        // Si la table est en cache et qu'elle est à jour, rien à compiler
        $cache = $this->fileCache();
        if ($cache->has($this->path, filemtime($this->path))) {
            return $cache->path($this->path);
        }
        $path = $cache->path($this->path);

        // Charge le fichier php
        $data = require_once $this->path;

        // Charge les entêtes de colonne
        $this->fields = array_shift($data);

        $sql = $this->parseFields();

        $this->db = $this->createSQLiteDatabase($path, $sql);
        $this->commit = true;

        // Prépare le statement utilisé pour charger les données
        $sql = sprintf
        (
            'INSERT INTO "data"("%s") VALUES (%s);',
            implode('","', $this->fields),
            rtrim(str_repeat('?,', count($this->fields)), ',)')
        );
        $statement = $this->db->prepare($sql);

        // Charge les données
        $index = array_flip($this->fields);
        foreach ($data as $values) {
            $allvalues = $values;
            foreach($values as $i => $value) {
                if (isset($index['_' . $this->fields[$i]])) {
                    $allvalues[] = implode(' ', Tokenizer::tokenize($value));
                }
            }
            $statement->execute($allvalues);
        }

        // Ferme le curseur
        $statement->closeCursor();

        // Retourne false pour indiquer que la base est déjà ouverte
        return false;
    }

    public function type() {
        return 'php';
    }
}