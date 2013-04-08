<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;

use Countable, IteratorAggregate, ArrayAccess;

use ArrayIterator, ReflectionClass;

/**
 * Une collection de paramètres constitués d'un nom et d'une valeur associée.
 *
 * Certaines méthodes retourne $this pour permettre de chainer les appels de
 * méthodes :
 *
 * <code>
 * $parameters->set('query', 'health')->set('format', 'html')->clear('max');
 * </code>
 */
class Parameters implements Countable, IteratorAggregate, ArrayAccess {
	/**
	 * Les paramètres de la requête
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * Construit une nouvelle collection de paramètres.
	 *
	 * Des paramètres supplémentaires peuvent être ajoutés à la collection
	 * en utilisant {@link set()} et {@link add()}
	 *
	 * @param array $parameters les paramètres initiaux de la collection.
	 */
	public function __construct(array $parameters = array()) {
		$this->parameters = $parameters;
	}

	/**
	 * Méthode statique permettant de créer une nouvelle instance.
	 *
	 * Cette méthode permet de créer une instance et de chainer les appels
	 * en une seule ligne :
	 *
	 * <code>
	 * $parameters = Parameters::create()->set('max', 10);
	 * </code>
	 *
	 * @param mixed $parameters Consultez la documentation du constructeur
	 * pour connaître les paramètres à indiquer.
	 *
	 * @return self $this
	 */
	public static function create(array $parameters = array()) {
		$class = new ReflectionClass(get_called_class());

		return $class->newInstanceArgs(func_get_args());
	}

	/**
	 * Retourne une copie (un clone) de la collection.
	 *
	 * @return self $this
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * Ajoute un paramètre à la collection ou une valeur à un paramètre existant.
	 *
	 * Add ajoute le paramètre indiqué à la liste des paramètres de la requête.
	 *
	 * Si le paramètre indiqué existait déjà, la valeur existante est
	 * transformée en tableau et la valeur indiquée est ajoutée au tableau
	 * obtenu.
	 *
	 * Pour remplacer complètement la valeur d'un paramètre existant, utiliser
	 * la méthode {@link set()}.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return self $this
	 */
	public function add($key, $value) {
		// Si la clé n'existe pas déjà, on l'insère à la fin du tableau
		if (!array_key_exists($key, $this->parameters)) {
			$this->parameters[$key] = $value;
			return $this;
		}

		// La clé existe déjà
		$item = &$this->parameters[$key];

		// Si c'est déjà un tableau, ajoute la valeur à la fin du tableau
		if (is_array($item)) {
			$item[] = $value;
		}

		// Sinon, crée un tableau avec la valeur existante et la valeur indiquée
		else {
			$item = array($item, $value);
		}

		return $this;
	}

	//TODO : pourquoi a-t-on un code différent entre add() et addParameter()
	// i.e. peut-on appeller add() dans la boucle de addParameters() ?

	/**
	 * Ajoute un tableau de paramètres à la collection.
	 *
	 * @param array $parameters
	 *
	 * @return self $this
	 */
	public function addParameters(array $parameters) {
		foreach ($parameters as $key => $value) {
			// Si la clé n'existe pas déjà, on l'insère à la fin du tableau
			if (!array_key_exists($key, $this->parameters)) {
				$this->parameters[$key] = $value;

				continue;
			}

			// Existe déjà, c'est un tableau, ajoute la valeur à la fin
			if (is_array($this->parameters[$key])) {
				// tableau + tableau
				if (is_array($value)) {
					$this->parameters[$key] = array_merge($this->parameters[$key], $value);
				}

				// tableau + valeur
				else {
					$this->parameters[$key][] = $value;
				}
			}

			// Existe déjà, simple valeur, crée un tableau contenant la valeur existante et la valeur indiquée
			else {
				// valeur + tableau
				if (is_array($value)) {
					$this->parameters[$key] = array_merge(array($this->parameters[$key]), $value);
				}

				// valeur + valeur
				else {
					$this->parameters[$key] = array($this->parameters[$key], $value);
				}
			}
		}

		return $this;
	}

	/**
	 * Retourne la valeur du paramètre indiqué ou la valeur par défaut spécifiée
	 * si le paramètre indiqué ne figure pas dans la collection.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null) {
		if (array_key_exists($key, $this->parameters)) {
			return $this->parameters[$key];
		}

		return $default;
	}

	/**
	 * Modifie la valeur d'un paramètre.
	 *
	 * Exemple :
	 *
	 * <code>
	 * $parameters->set('item', 12)
	 * </code>
	 *
	 * Set remplace complètement la valeur existante. Pour ajouter une valeur
	 * à un paramètre existant, utiliser {@link add()}
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return self $this
	 */
	public function set($key, $value) {
		$this->parameters[$key] = $value;

		return $this;
	}

	/**
	 * Supprime des paramètres de la collection.
	 *
	 * Avec cette méthode, vous pouvez supprimer :
	 * - tous les paramètres qui figure dans la collection : <code>clear()</code>
	 * - un paramètre unique : <code>clear('key')</code>
	 * - une valeur précise d'un paramètre : <code>clear('key', 'value')</code>
	 *
	 * @param string $key le nom du paramètre à supprimer.
	 *
	 * @param mixed $value optionnel : la valeur à effacer.
	 *
	 * Par défaut (lorsque $value n'est pas indiqué, clear efface complètement
	 * le paramétre indiqué par $key. Si $value est indiqué et que $key désigne
	 * un tableau, seule la valeur indiquée va être supprimée de la requête.
	 * Si $key désigne un scalaire, le paramètre ne sera supprimé que si la
	 * valeur associée correspond à $value (===).
	 *
	 * @return self $this
	 */
	public function clear($key = null, $value = null) {
		// Vider la collection
		if (is_null($key)) {
			$this->parameters = array();

			return $this;
		}

		// Supprimer un paramètre
		if (is_null($value)) {
			unset($this->parameters[$key]);

			return $this;
		}

		// Supprimer une valeur
		if (array_key_exists($key, $this->parameters)) {
			$v = $this->parameters[$key];
			if (is_scalar($v)) {
				if ($v === $value) {
					unset($this->parameters[$key]);
				}
			} else {
				foreach ($this->parameters[$key] as $k => $v) {
					if ($v === $value) {
						unset($this->parameters[$key][$k]);
					}
				}

				if (empty($this->parameters[$key])) {
					unset($this->parameters[$key]);
				}
			}
		}

		return $this;
	}

	/**
	 * Supprime tous les paramètres sauf ceux dont le nom est indiqué.
	 *
	 * Exemple :
	 * <code>
	 * $parameters->only('max', 'format'); // supprime tous sauf max et format
	 * </code>
	 *
	 * @param string $arg... nom des paramètres à conserver.
	 *
	 * @return self $this
	 */
	public function only($arg) {
		$args = func_get_args();
		$this-> parameters = array_intersect_key($this->parameters, array_flip($args));

		return $this;
	}

	/**
	 * Supprime tous les paramètres vides.
	 *
	 * La méthode <code>clearNull()</code> supprime de la collection tous les
	 * paramètres dont la valeur est une chaine vide, un tableau vide ou la
	 * valeur null.
	 *
	 * @return self $this
	 */
	public function clearNull() {
		foreach ($this->parameters as $key => &$value) {
			if ($value === null || $value === '' || $value === array()) {
				unset($this->parameters[$key]);
			} elseif (is_array($value)) {
				foreach ($value as $key => $item) {
					if ($item === null || $item === '' || $item === array()) {
						unset($value[$key]);
					}
				}

				if (empty($value)) {
					unset($this->parameters[$key]);
				}
			}
		}

		return $this;
	}

	/**
	 * Indique si la collection contient des paramètres.
	 *
	 * @return bool
	 */
	public function hasParameters() {
		return count($this->parameters) !== 0;
	}

	/**
	 * Retourne tous les paramètres présents dans la collection.
	 *
	 * @return array
	 */
	public function all() {
		return $this->parameters;
	}

	/**
	 * Détermine si le paramètre indiqué existe.
	 *
	 * La fonction retourne true même si le paramètre à la valeur null
	 *
	 * @param string $key le nom du paramètre à tester.
	 * @param mixed $value optionnel, la valeur à tester. Lorsque $value
	 * est indiquée, la méthode retourne true si le paramètre $key est définit
	 * et s'il contient la valeur $value.
	 *
	 * @return bool
	 */
	public function has($key, $value = null) {
		if (!array_key_exists($key, $this->parameters)) {
			return false;
		}

		if (is_null($value)) {
			return true;
		}

		foreach ((array) $this->parameters[$key] as $v) {
			if ($v === $value)
				return true;
		}

		return false;
	}

	/**
	 * Retourne un dump des paramètres en cours
	 *
	 * __toString est une méthode magique de php qui est appellée lorsque PHP
	 * a besoin de convertir un objet en chaine de caractères.
	 *
	 * @return string
	 */
	public function __toString() {
		return print_r($this->parameters, true);
	}

	// METHODES MAGIQUES

	/**
	 * Retourne la valeur du paramètre indiqué ou null si la collection ne
	 * contient pas le paramètre demandé.
	 *
	 * __get est une méthode magique de php qui permet d'accéder aux paramètres
	 * de la collection comme s'il s'agissait de propriétés de l'objet
	 * Parameters (par exemple <code>$parameters->max</code>)
	 *
	 * La méthode {@link get()} est similaire mais permet d'indiquer une valeur
	 * par défaut.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get($key) {
		return $this->get($key);
	}

	/**
	 * Modifie la valeur d'un paramètre.
	 *
	 * __set est une méthode magique de php qui permet de modifier un
	 * paramètre comme s'il s'agissait d'une propriété de l'objet Parameters
	 * (par exemple <code>$parametrs->max = 10</code>)
	 *
	 * Set remplace complètement la valeur existante. Pour ajouter une valeur
	 * à un paramètre existant, utiliser {@link add()}
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Supprime le paramètre indiqué
	 *
	 * __unset est une méthode magique de php qui permet de supprimer un
	 * paramètre comme s'il s'agissait d'une propriété de l'objet Parameters
	 * (par exemple <code>unset($parameters->max)</code>)
	 *
	 * @param string $key
	 */
	public function __unset($key) {
		unset($this->parameters[$key]);
	}

	/**
	 * Détermine si le paramètre indiqué existe.
	 *
	 * __isset() est une méthode magique de php qui permet de tester l'existence
	 * d'un paramètre comme s'il s'agissait d'une propriété de l'objet Parameters.
	 *
	 * La fonction {@link has()} fait la même chose mais prend le nom de
	 * l'argument en paramètre.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function __isset($key) {
		return $this->has($key);
	}

	// Interface Countable

	/**
	 * Retourne le nombre de paramètres dans la collection.
	 *
	 * @return int
	 */
	public function count () {
	    return count($this->parameters);
	}

	// Interface IteratorAggregate

	/**
	 * Retourne un itérateur sur les paramètres de la collection.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator () {
	    return new ArrayIterator($this->parameters);
	}

	// Interface ArrayAccess

	/**
	 * Supprime un paramètre.
	 *
	 * offsetExists permet d'accéder aux paramètres de la collection comme s'il
	 * s'agissait de propriétés d'un tableau. Par exemple :
	 * <code>if (isset($parameters['max'])) {...}</code>
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function offsetExists($key) {
	    return $this->has($key);
	}

	/**
	 * Retourne la valeur du paramètre indiqué ou null si la collection ne
	 * contient pas le paramètre demandé.
	 *
	 * offsetGet permet d'accéder aux paramètres de la collection comme s'il
	 * s'agissait de propriétés d'un tableau. Par exemple :
	 * <code>$parameters['max']</code>
	 *
	 * La méthode {@link get()} est similaire mais permet d'indiquer une valeur
	 * par défaut.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function offsetGet($key) {
	    return $this->get($key);
	}

	/**
	 * Modifie la valeur d'un paramètre.
	 *
	 * offsetSet permet d'accéder aux paramètres de la collection comme s'il
	 * s'agissait de propriétés d'un tableau. Par exemple :
	 * <code>$parameters['max'] = 10</code>
	 *
	 * Set remplace complètement la valeur existante. Pour ajouter une valeur
	 * à un paramètre existant, utiliser {@link add()}
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value) {
	    $this->set($key, $value);
	}

	/**
	 * Supprime un paramètre.
	 *
	 * offsetUnset permet d'accéder aux paramètres de la collection comme s'il
	 * s'agissait de propriétés d'un tableau. Par exemple :
	 * <code>unset($parameters['max'])</code>
	 *
	 * @param string $key
	 */
	public function offsetUnset($key) {
	    $this->clear($key);
	}
}