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

use Docalist\Cache\FileCache;
use Docalist\Tokenizer;
use PDO;
use Exception;

/**
 * Classe de base pour les tables d'autorité.
 *
 */
class SQLite implements TableInterface {

    /**
     * Le path de la table de référence.
     *
     * @var string
     */
    protected $path;

    /**
     * Indique si la table peut être mise à jour ou non.
     *
     * Surchargé dans les classes descendantes
     *
     * @var boolean
     */
    protected $readonly = false;

    /**
     * Objet {@link http://php.net/PDO PDO} permettant d'accèder à la base de
     * données SQLite de la table en cours.
     *
     * @var PDO
     */
    protected $db = null;

    /**
     * Indique si on a une transaction (une écriture) en cours.
     *
     * @var boolean
     */
    protected $commit = null;

    /**
     * Tableau contenant les noms des champs de la table.
     *
     * @var array(string)
     */
    protected $fields = null;

    public function type() {
        return 'sqlite';
    }

    public function path() {
        return $this->path;
    }

    public function readOnly() {
        return $this->readonly;
    }

    public function fields($all = false) {
        // Retourne tous les champs
        if ($all) {
            return $this->fields;
        }

        // Ne retourne que les champs normaux
        $fields = array();
        foreach($this->fields as $field) {
            $field[0] !== '_' && $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Ouvre la table de référence dont le chemin est passé en paramètre.
     *
     * Les tables au format texte sont compilées à la volée : lors du tout
     * premier appel, la table est chargée puis est stockée dans une bases
     * de données {@link http://www.sqlite.org/ SQLite} stockée dans un
     * répertoire temporaire.
     *
     * Lors des appels suivants, la base SQLite est ouverte directement. Si le
     * fichier d'origine a été modifié, la table est recompilée automatiquement
     * pour mettre à jour la base de données.
     *
     * Les tables au format SQLite sont ouvertes directement sans compilation.
     *
     * @param string $path le path du fichier de la table à ouvrir.
     */
    public function __construct($path) {
        // Stocke le path de la table
        $this->path = $path;

        // Compile la table
        $path = $this->compile($path);

        // Si compile retourne false, c'est que la table a déjà été ouverte
        // Sinon elle retourne le path de la base sqlite à ouvrir
        // (éventuellement le même que celui qu'on lui a passé).
        if ($path) {

            // Ouvre la base
            $this->db = new PDO("sqlite:$path");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Démarre une transaction si la table est en read/write
            !$this->readonly && $this->commit = $this->db->beginTransaction();

            // Récupère le nom des champs de la table
            // @formatter:off
            $this->fields = $this->db->query('PRAGMA table_info(data)')
                ->fetchAll(PDO::FETCH_NUM | PDO::FETCH_COLUMN, 1);
            // @formatter:on
        }
    }

    /**
     * Destructeur. Committe les éventuelles modifications apportées à la table
     * et ferme la base SQLite.
     */
    public function __destruct() {
        // Si la table a été ouverte en écriture, committe les modifications
        $this->commit && $this->db->commit();

        // Ferme la connexion
        unset($this->db);
    }

    /**
     * Retourne le cache à utiliser pour les tables compilées.
     *
     * Utilise le filtre <code>docalist_get_file_cache</code> pour récupérer
     * le cache en cours.
     *
     * @return FileCache
     *
     * @throws Exception Si aucun cache n'est disponible.
     */
    protected function fileCache() {
        $cache = apply_filters('docalist_get_file_cache', null);
        if (is_null($cache)) {
            $msg = __('Aucun FileCache disponible', 'docalist-core');
            throw new Exception($msg);
        }

        return $cache;
    }

    /**
     * Compile la table.
     *
     * Crée dans le cache une base de données SQLite contenant une copie des
     * données de la table.
     *
     * Si la base est déjà en cache (et qu'elle est à jour), la méthode ne fait
     * rien et retourne directement le path de la base dans le cache.
     *
     * @return string|false Le path de la base SQLite à ouvrir ou false si la
     * base a été créée lors de la compilation (dans ce cas elle est déjà
     * ouverte, la valeur "false" retournée signifie "ne pas réouvrir").
     */
    protected function compile() {
        // pour une table au format sqlite, on n'a rien à compiler
        // on ouvre directement le fichier.
        return $this->path;
    }

    /**
     * Analyse la ligne d'entête d'une table et retourne la requête sql
     * permettant de créer la table des données et les index indiqués dans
     * les entêtes de la table.
     *
     * En entrée, $this->fields est un tableau décrivant les champs de la table.
     * Chaque champ peut être sous la forme "nom (type contraintes)" (cf. doc
     * de {@link http://www.sqlite.org/lang_createtable.html SQLite}).
     *
     * En sortie, $this->fields est modifié pour ne contenir que le nom des
     * champs (sans les contraintes).
     *
     * @return string la requête sql permettant de créer la table et les index
     * indiqués dans $fields.
     *
     * @throw Exception si la syntaxe de la ligne d'entête est incorrecte.
     */
    protected function parseFields() {
        // Chaine de base pour les messages d'erreur
        $err = __('Erreur dans la table %s. ', 'docalist-core');

        // Examine tous les champs
        $_names = $names = $_defs = $defs = $index = array();
        foreach ($this->fields as $field) {

            // Sépare le nom du champ de ses paramètres
            $pt = strpos($field, '(');
            if ($pt) {
                $name = substr($field, 0, $pt - 1);
                $parms = trim(substr($field, $pt + 1), ' )');
            } else {
                $name = $field;
                $parms = 'INDEX, COLLATE NOCASE';
            }

            // Analyse les paramètres indiqués
            $parms = explode(',', $parms);
            $type = '';
            $_constraints = $constraints = array();
            foreach ($parms as $parm) {
                $parm = trim($parm);
                switch (strtolower($parm)) {
                    case '':
                        break;

                    case 'integer':
                    case 'float':
                    case 'real':
                    case 'numeric':
                    case 'boolean':
                    case 'time':
                    case 'date':
                    case 'timestamp':
                    case 'varchar':
                    case 'nvarchar':
                    case 'text': // valeur par défaut
                    case 'blob':
                        if ($type) {
                            $msg = __('Champ %s : vous ne pouvez pas indiquer à la fois %s et %s.', 'docalist-core');
                            throw new Exception($err . sprintf($msg, $name, $type, $parm));
                        }
                        $type = strtoupper($parm);
                        break;

                    case 'primary key':
                    case 'not null':
                    case 'unique':
                        $_constraints[] = strtoupper($parm);
                        break;

                    case 'index':
                        $index[] = sprintf('CREATE INDEX "%s" ON "data" ("_%s" ASC);', $name, $name);
                        break;

                    default:
                        if (strncasecmp($parm, 'default ', 8) === 0) {
                            $constraints[] = 'DEFAULT ' . substr($parm, 8);
                        } elseif (strncasecmp($parm, 'collate ', 8) === 0) {
                            $constraints[] = strtoupper($parm);
                        } else {
                            $msg = __('Champ %s : paramètre non géré "%s".', 'docalist-core');
                            throw new Exception($err . sprintf($msg, $name, $parm));
                        }
                }
            }

            // Stocke la définition du champ
            empty($type) && $type = 'TEXT';

            $names[] = $name;
            $def = "\"$name\" $type";
            if ($constraints) {
                $def .= ' ' . implode(' ', $constraints);
            }
            $defs[] = $def;

            $_names[] = "_$name";
            $def = "\"_$name\" $type";
            if ($_constraints) {
                $def .= ' ' . implode(' ', $_constraints);
            }

            $_defs[] = $def;
        }

        // Crée la requête sql permettant de créer la table et ses index
        $defs = array_merge($defs, $_defs);
        $names = array_merge($names, $_names);
        $sql = 'CREATE TABLE "data"(' . implode(', ', $defs) . ');';
        if ($index) {
            $sql .= "\n" . implode("\n", $index);
        }

        $this->fields = $names;

        return $sql;
    }

    /**
     * Crée et initialise une base de données SQLite en créant la table et les
     * index requis.
     *
     * @param string $path le path de la base de données à créer.
     *
     * @param string $sql une chaine contenant les requêtes sql permettant de
     * créer la table de données et les index requis telle que retournée
     * par {@link parseFields()}.
     *
     * @return PDO l'objet PDO représentant la base créée.
     */
    protected function createSQLiteDatabase($path, $sql) {
        // Crée le répertoire de la base de données si nécessaire
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
            $msg = __('Impossible de créer le répertoire "%s"', 'docalist-core');
            throw new Exception(sprintf($msg, $dir));
        }

        // Supprime la base de données existante si nécessaire
        if (file_exists($path) && !@unlink($path)) {
            $msg = __('Impossible de supprimer le fichier "%s"', 'docalist-core');
            throw new Exception(sprintf($msg, $path));
        }

        // Crée la base de données SQLite
        $db = new PDO("sqlite:$path");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->commit = $db->beginTransaction();

        // Crée la table contenant les données et les index indiqués dans la requête sql
        $db->exec($sql);

        // Retourne la table créée
        return $db;
    }

    /**
     * Exécute une requête Select sur la table.
     *
     * La méthode search exécute une requête SQL de la forme :
     * <code>
     *     SELECT $what FROM table
     *     [WHERE $where]
     *     [ORDER BY $order]
     *     [LIMIT $limit [OFFSET $offset]];
     * </code>
     *
     * Exemples d'utilisation :
     * <code>
     *     // Tous les enregistrements de la table
     *     $table->search();
     *
     *     // Tous les pays dont le code est "fra" (un seul normalement)
     *     $countries->search('name', '_alpha3="fra"');
     *
     *     // Les 10 premiers pays qui commencent par 'egy', triés par nom
     *     $countries->search('*', '_name LIKE "egy%"', 'name', 10);
     * </code>
     *
     * @param string $what Champs à retourner (* par défaut).
     * @param string $where Critères de recherche de la clause WHERE.
     * @param string $order Ordre de tri.
     * @param int $limit Nombre de réponses maximum.
     * @param int $offset Offset des réponses.
     * @param int $fetchMode Une combinaison de constantes PDO:FETCH_XXX.
     *
     * @return array La méthode retourne toujours un tableau (éventuellement
     * vide) contenant les réponses obtenues. Le format de chaque élément
     * dépend de $fetchMode (cf. doc de PDO).
     */
    protected function query($what = '*', $where = '', $order = '', $limit = null, $offset = null, $fetchMode = PDO::FETCH_OBJ) {
        // Construit la requête sql
        $sql = "SELECT $what FROM data";
        $where && $sql .= " WHERE $where";
        $order && $sql .= " ORDER BY $order";
        $limit && $sql .= " LIMIT $limit";
        $offset && $sql .= " OFFSET $offset";

        // Exécute la requête
        $statement = $this->db->query($sql);

        // Récupère les réponses
        $result = $statement->fetchAll($fetchMode);

        // Ferme la requête et retourne le résultat
        $statement->closeCursor();

        return $result;
    }

    public function search($what = '*', $where = '', $order = '', $limit = null, $offset = null) {
        $nb = (strpos($what, '*') === false) ? substr_count($what, ',') : 2;
        switch ($nb) {
            // Un seul champ : tableau de valeurs
            case 0:
                $fetchMode = PDO::FETCH_COLUMN;
                break;

            // Deux champs : tableau associatif champ1 => champ2
            case 1:
                $fetchMode = PDO::FETCH_KEY_PAIR;
                break;

            // Plus de deux : tableau associatif premier champ => objet
            default:
                $fetchMode = PDO::FETCH_OBJ | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE;
                break;
        }

        return $this->query($what, $where, $order, $limit, $offset, $fetchMode);
    }

    public function find($what = '*', $where = '') {
        $result = $this->search($what, $where, '', 1);
        return empty($result) ? null : reset($result);
    }

    public function lookup($what = '*', $prefix, $limit = null) {
        // On recherche et on trie sur le premier champ indiqué
        $field = strtok($what, ',');
        $field === '*' && $field = $this->fields[0];

        // La recherche et le tri sont insensibles à la casse et aux accents
        $field = '_' . $field;

        // Tokenise le préfixe recherché
        $prefix = implode(' ', Tokenizer::tokenize($prefix));
        $prefix = $this->db->quote($prefix . '%');

        // Construit la clause where
        $where = sprintf('%s LIKE %s', $field, $prefix);

        // Retourne les résultats
        return $this->search($what, $where, $field, $limit);
    }
}