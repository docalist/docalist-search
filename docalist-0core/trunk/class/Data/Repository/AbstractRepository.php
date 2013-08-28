<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Data\Repository;

use Docalist\Data\Entity\EntityInterface;
use Docalist\Utils;
use InvalidArgumentException;

/**
 * Classe de base abstraite pour implémenter un dépôt.
 *
 * La classe se contente de fournir des méthodes utilitaires :
 * - type
 * - checkType
 * - checkPrimaryKey
 */
abstract class AbstractRepository implements RepositoryInterface {
    /**
     * Le type du dépôt.
     *
     * @var string
     */
    protected $type;

    /**
     * Crée un nouveau dépôt.
     *
     * @param string $type le nom complet de la classe Entité utilisée pour
     * représenter les enregistrements de ce dépôt.
     *
     * @throws InvalidArgumentException Si $type ne désigne pas une classe d'entité.
     */
    public function __construct($type) {
        // Vérifie que la classe indiquée existe
        if (! class_exists($type)) {
            // Nom de classe relatif au namespace de la classe en cours ?
            $class = Utils::ns(get_class($this)) . '\\' . $type;
            if (! class_exists($class)) {
                $msg = 'Invalid entity type "%s" in repository "%s": class not found';
                throw new InvalidArgumentException(sprintf($msg, $type, get_class($this)));
            }
            $type = $class;
        }

        $this->checkType($type, 'Docalist\Data\Entity\EntityInterface');
        $this->type = $type;
    }

    public function type() {
        return $this->type;
    }

    /**
     * Vérifie que l'objet ou le nom de classe passé en paramètre correspond
     * au type indiqué et génère une exception si ce n'est pas le cas.
     *
     * @param string|object $test Le nom de classe ou l'objet à tester.
     *
     * @param string $type Optionnel, le type que doit avoir $test. Si aucun
     * type n'est indiqué, $test sera comparé au type de l'entrepôt.
     *
     * @return bool Retourne true si $test a le bon type.
     *
     * @throws InvalidArgumentException Si le test échoue.
     */
    protected function checkType($test, $type = null) {
        // Pas de type indiqué : compare avec le type du dépôt
        is_null($type) && $type = $this->type();

        // Si test est du bon type, terminé
        if (is_a($test, $type, true)) {
            return true;
            // is_a ou instanceof ?
            // - test peut être soit un nom de classe, soit un objet
            // - type peut être un nom de classe ou un nom d'interface
            // is_a est le seul qui fonctionne dans tous les cas
            // apparemment class instanceof class ne fonctionne pas bien
        }

        // Erreur
        $msg = 'Incorrect entity type "%s", expected "%s"';
        is_object($test) && $test = get_class($test);
        throw new InvalidArgumentException(sprintf($msg, $test, $type));
    }

    /**
     * Extrait l'id de l'entité à partir du paramètre indiqué.
     *
     * @param scalar|EntityInterface $entity
     *
     * @param bool $required Indique s'il faut obtenir un ID non vide.
     *
     * @return scalar Si $entity est déjà un ID, il est retourné tel quel.
     * Si $entity est une entité, la méthode retourne l'ID de l'entité.
     *
     * @throws InvalidArgumentException si le type de $entity n'est pas correct
     * ou si $required est à true et que l'ID obtenu est vide.
     *
     */
    protected function checkPrimaryKey($entity, $required = false) {
        if (is_scalar($entity)) {
            $primaryKey = $entity;
        } elseif (is_object($entity)) {
            $this->checkType($entity);
            $primaryKey = $entity->primaryKey();
        }

        if ($required && empty($primaryKey)) {
            $msg = 'Entity primary key is required';
            throw new InvalidArgumentException($msg);
        }

        return $primaryKey;
    }
}