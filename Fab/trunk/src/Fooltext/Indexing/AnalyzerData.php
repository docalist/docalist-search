<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Indexing
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Indexing;

use Fooltext\Schema\Field;

/**
 * Structure contenant le champ à analyser et dans laquelle les analyseurs
 * stockent les termes qu'ils produisent.
 *
 */
class AnalyzerData
{
    /**
     * La définition du champ en cours d'analyse, telle qu'il figure
     * dans le {@link \Fooltext\Schema\Schema schéma} de la base de données.
     *
     * @var \Fooltext\Schema\Field
     */
    public $field;

    /**
     * Le contenu du champ à analyser.
     *
     * @var array
     */
    public $content = array();

    /**
     * Les termes simples générés pour ce champ.
     *
     * @var array
     */
    public $terms = array();

    /**
     * Les termes avec position générés pour ce champ.
     *
     * @var array
     */
    public $postings = array();

    /**
     * Les motsclés (termes sans poids ni position) générés pour ce champ.
     *
     * @var array
     */
    public $keywords = array();

    /**
     * Les entrées de correcteur orthographique générées pour ce champ.
     *
     * @var array
     */
    public $spellings = array();

    /**
     * Les entrées de table de lookup générées pour ce champ
     *
     * @var array
     */
    public $lookups = array();

    /**
     * Les attributs (clés de tri, etc.) générés pour ce champ.
     *
     * @var array
     */
    public $sortkeys = array();

    /**
     * Constructeur.
     *
     * @param Field $field la définition (dans le schéma) du champ à analyser.
     * @param mixed $data le contenu du champ à analyser.
     */
    public function __construct(Field $field, $data)
    {
        $this->field = $field;
        $this->content = $data==='' ? array() : (array) $data;
    }

    /**
     * Exécute un callback sur chacun des termes présents dans une
     * ou plusieurs des propriétés de l'objet AnalyzerData.
     *
     * Le callback peut modifier comme il veut chacun des termes
     * passés en paramètre.
     *
     * @param string|array $what indique les propriétés de l'objet
     * AnalyzerData auxquelles le callback sera appliqué (exemple :
     * 'keywords' ou array('terms','postings').
     *
     * @param callback $callback le callback à appliquer à chaque
     * terme. Le callback recevra au moins deux arguments : la
     * valeur du terme et sa clé (utile par exemple pour les postings,
     * pour avoir la position du terme). Il recevra également tous les
     * arguments supplémentaires passés dans $args.
     *
     * @return AnalyzerData $this.
     */
    public function map($what, $callback)
    {
        foreach((array)$what as $property)
        {
            foreach($this->$property as $key => & $value)
            {
                if (is_scalar($value))
                {
                    $value = call_user_func($callback, $value); // php 5.4 : $callback($value);
                    if (is_null($value)) unset($this->$property[$key]);
                }
                else// is_array
                {
                    foreach($value as $key2 => & $item)
                    {
                        $item = call_user_func($callback, $item); // php 5.4 : $callback($item);
                        if (is_null($item)) unset($value[$key2]);
                    }
                    if (count($value) === 0) unset($this->$property[$key]);
                }
            }
        }
        return $this;

        // La version "foreach" ci-dessus est quasiment 2 fois
        // plus rapide que la version "closure" ci-dessous...

        /*
        $this->$property = array_filter($this->$property, function(& $item) use($callback)
        {
            if (is_scalar($item))
            {
                $item = $callback($item);
                return $item !== null;
            }
            else
            {
                $item = array_filter($item, function(& $item) use($callback)
                {
                    $item = $callback($item);
                    return $item !== null;
                });
                return count($item) !== 0;
            }
        });
        */
    }

    /**
     * Appelle un callback pour chacun des termes présents dans une
     * ou plusieurs des propriétés de l'objet AnalyzerData.
     *
     * @param string|array $what indique les propriétés de l'objet
     * AnalyzerData auxquelles le callback sera appliqué (exemple :
     * 'keywords' ou array('terms','postings').
     *
     * @param callback $callback le callback à appliquer à chaque
     * terme. Le callback recevra au moins deux arguments : la
     * valeur du terme et sa clé (utile par exemple pour les postings,
     * pour avoir la position du terme). Il recevra également tous les
     * arguments supplémentaires passés dans $args.
     *
     * @param mixed $args un ou plusieurs arguments supplémentaires à
     * passer au callback.
     *
     * @return AnalyzerData $this.
     */
    public function walk($what, $callback, $args=null)
    {
        $args = func_get_args();

        foreach((array)$what as $property)
        {
            foreach($this->$property as $key => $value)
            {
                if (is_scalar($value))
                {
                    $args[0] = $value;
                    $args[1] = $key;
                    call_user_func_array($callback, $args);
                }
                else// is_array
                {
                    foreach($value as $key => $value)
                    {
                        $args[0] = $value;
                        $args[1] = $key;
                        call_user_func_array($callback, $args);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Dump le contenu de l'objet.
     *
     * @param string $title titre optionnel à afficher avant le dump.
     *
     * @return AnalyzerData $this.
     */
    public function dump($title='')
    {
        if ($title) echo $title, " :\n", str_repeat('-', strlen($title)+2), "\n";
        $this
            ->_dump('Content', $this->content)
            ->_dump('Terms', $this->terms)
            ->_dump('Postings', $this->postings, true)
            ->_dump('Keywords', $this->keywords)
            ->_dump('Spellings', $this->spellings)
            ->_dump('Lookups', $this->lookups)
            ->_dump('Sortkeys', $this->sortkeys);
        echo "\n";
        return $this;
    }

    /**
     * Méthode utilisée par {@link dump()}.
     *
     * Affiche le contenu d'un groupe de termes.
     *
     * @param string $title titre à affficher pour le groupe.
     * @param array $list termes à afficher.
     * @param boolean $keys flag indiquant s'il faut ou non afficher
     * la clé des termes (utile uniquement pour les postings).
     *
     * @return AnalyzerData $this.
     */
    protected function _dump($title, array $list, $keys=false)
    {
        if (count($list) === 0) return $this;
        echo $title, " :\n";
        foreach($list as $value)
        {
            if (is_scalar($value))
            {
                echo '   - ', $value, "\n";
            }
            else
            {
                echo '   - ';
                $first = true;
                foreach($value as $key => $item)
                {
                    if (! $first) echo ' | '; else $first = false;
                    if ($keys)
                        echo $key, ':', $item;
                    else
                        echo $item;
                }
                echo "\n";
            }
        }
        return $this;
    }
}