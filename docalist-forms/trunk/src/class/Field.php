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
 * @version     SVN: $Id: Field.php 397 2013-02-11 15:30:06Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist\Forms;

use ArrayAccess, Exception, XmlWriter;
use Docalist\Data\Schema\FieldInterface;

/**
 * Un champ de formulaire.
 *
 * Un champ est un élément de formulaire. Il dispose d'attributs et il a un nom
 * et une valeur. Il peut également avoir un libellé et une description.
 */
abstract class Field {
    /**
     * @var array Liste des attributs booléens qui existent en html 5.
     *
     * cf http://www.w3.org/TR/html5/infrastructure.html#boolean-attribute
     *
     * Cette liste a été constituée "à la main" en recherchant la chaine
     * "boolean attribute" dans la page http://www.w3.org/TR/html5/index.html.
     */
    protected static $booleanAttributes = array(
        'async' => true,
        'autofocus' => true,
        'autoplay' => true,
        'checked' => true,
        'controls' => true,
        'default' => true,
        'defer' => true,
        'disabled' => true,
        'formnovalidate' => true,
        'hidden' => true,
        'ismap' => true,
        'loop' => true,
        'multiple' => true,
        'muted' => true,
        'novalidate' => true,
        'open' => true,
        'readonly' => true,
        'required' => true,
        'reversed' => true,
        'scoped' => true,
        'seamless' => true,
        'selected' => true,
        'typemustmatch' => true,
    );

    /**
     * @var string Thème en cours utilisé pour le rendu.
     */
    protected static $theme;

    /**
     * @var XMLWriter Le générateur XML utilisé pour générer le code html
     * du formulaire.
     */
    protected static $writer;

    /**
     * @var bool Indique si l'option "indent" a été activée pour la génération
     * de code en cours (lors de l'appel à {@link render()}).
     *
     * Cette propriété est interne (protected) et statique. Elle est utilisée
     * en interne par les templates pour leur permettre de désactiver
     * temporairement l'indentation faite par XMLWriter lorsque celle-ci ne
     * convient pas (par exemple lorsqu'on insère du texte : xmlwriter ne peut
     * pas "inventer" de nouveaux espaces et donc il ne peut pas indenter le
     * code.
     *
     * Pour un exemple d'utilisation, voir default/checklist.option.php.
     */
    protected static $indent;

    /**
     * @var bool Indique si l'option "comment" a été activée
     */
    protected static $comment;

    /**
     * @var array Liste des ID déjà utilisés pour le rendu en cours.
     */
    protected static $usedId;

    /**
     * @var int Occurence en cours (lors du rendu)
     */
    protected $occurence = 0;

    /**
     * @var array Pile utilisée pour le rendu des templates.
     */
    protected $callStack = array();

    /**
     * @var Fields Le bloc parent de cet élément.
     */
    protected $parent;

    /**
     * @var array Attributs de l'élément.
     */
    protected $attributes = array();

    /**
     * @var string Nom de l'élément.
     */
    protected $name;

    /**
     * @var string Libellé associé à l'élément.
     */
    protected $label;

    /**
     * @var string Description de l'élément.
     */
    protected $description;

    /**
     * @var Position de la description.
     *
     * Par défaut, le bloc description est affiché après le champ.
     * Lorsque cette propriété est à true, elle est affichée avant.
     *
     * Cette propriété peut être modifiée en passant un paramètre
     * suplémentaire à la méthode {description()}.
     */
    protected $descriptionAfter = true;

    /**
     * @var mixed Les données du champ.
     */
    protected $data;

    /**
     * @var bool Indique si le champ est répétable.
     */
    protected $repeatable = false;

    /**
     *
     * @var FieldInterface
     */
    protected $schema;

    /**
     * Crée un nouveau champ.
     *
     * @param string $name Le nom du champ.
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * Retourne une chaine indiquant le type du champ.
     *
     * Par convention, le type du champ correspond à la version en minuscules
     * du dernier élément du nom de classe.
     *
     * Par exemple, le type des éléments {@link Input} est "input".
     *
     * @return string
     */
    public final function type() {
        return strtolower(substr(strrchr(get_class($this), '\\'), 1));
    }

    /**
     * Retourne le parent de ce champ ou null s'il n'a pas encore été ajouté
     * dans une {@link Fields liste de champs}.
     *
     * @return Fields
     */
    public function parent() {
        return $this->parent;
    }

    /**
     * Retourne l'élément racine de la hiérarchie, c'est-à-dire l'élément de
     * plus haut niveau qui contient ce champ ou null s'il n'a pas encore été
     * ajouté dans une {@link Fields liste de champs}.
     *
     * @return Fields
     */
    public function root() {
        return $this->parent ? $this->parent->root() : $this;
    }

    /**
     * Retourne la profondeur du champ, c'est-à-dire le niveau auquel il
     * se trouve dans la hiérarchie.
     *
     * L'élément de plus haut niveau à une profondeur de 0, ses enfants une
     * profondeur de 1 et ainsi de suite.
     *
     * @return int
     */
    public function depth() {
        return $this->parent ? 1 + $this->parent->depth() : 0;
    }

    /**
     * Retourne ou modifie les attributs du champ.
     *
     * @param array $attributes Un tableau de la forme nom de l'attribut =>
     * contenu de l'attribut.
     *
     * @return array|$this
     */
    public function attributes(array $attributes = null) {
        if (is_null($attributes))
            return $this->attributes;

        // @todo : tester si on a des attributs booléen dans la liste
        foreach ($attributes as $name => $value) {
            $this->attribute($name, $value);
        }

        return $this;
    }

    /**
     * Retourne ou modifie la valeur d'un attribut.
     *
     * Appellée avec un seul paramètre, la méthode retourne la valeur de
     * l'attribut demandé ou null si celui-ci n'existe pas.
     *
     * Appellée avec deux paramètres, la méthode modifie la valeur de
     * l'attribut indiqué. Si la valeur est vide (false, '', etc.) l'attribut
     * est supprimé.
     *
     * La méthode reconnait les
     * {@link http://www.w3.org/TR/html5/infrastructure.html#boolean-attribute
     * attributs booléens} tels que selected="selected" ou checked="checked".
     * Dans ce cas, vous pouvez utiliser true pour activer un attribut.
     * Exemple :
     * <code>
     * $field->attribute('checked', true);
     * echo $field->attribute('checked'); // retourne 'checked'
     * </code>
     *
     * @param string $name le nom de l'attribut (en minuscules).
     * @param string $value la valeur de l'attribut
     *
     * @return string|$this
     */
    public function attribute($name, $value = null) {
        // Getter
        if (is_null($value)) {
            return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
        }

        // Setter
        if ($value) {
            $this->attributes[$name] = isset(self::$booleanAttributes[$name]) ? $name : $value;
        }

        // L'attribut à une valeur vide (false, '', etc.). On le supprime
        else {
            unset($this->attributes[$name]);
        }

        return $this;
    }

    /**
     * Ajoute une ou plusieurs classes à l'attribut class du champ.
     *
     * Chacune des classes indiquées n'est ajoutée à l'attribut que si elle
     * n'y figure pas déjà. Les noms de classes sont sensibles à la casse.
     *
     * @param string $class La classe à ajouter. Vous pouvez également ajouter
     * plusieurs classes en séparant leurs noms par un espace.
     *
     * Exemple $input->addClass('text small');
     *
     * @return $this
     */
    public function addClass($class) {
        if (!isset($this->attributes['class']) || empty($this->attributes['class'])) {
            $this->attributes['class'] = $class;
        } else {
            foreach (explode(' ', $class) as $class) {
                $pos = strpos(' ' . $this->attributes['class'] . ' ', " $class ");
                if ($pos === false) {
                    $this->attributes['class'] .= " $class";
                }
            }
        }

        return $this;
    }

    /**
     * Supprime une ou plusieurs classes de l'attribut class du champ.
     *
     * @param string $class La classe à supprimer. Vous pouvez également enlever
     * plusieurs classes en séparant leurs noms par un espace.
     *
     * Exemple $input->removeClass('text small');
     *
     * @return $this
     */
    public function removeClass($class) {
        if (isset($this->attributes['class'])) {
            foreach (explode(' ', $class) as $class) {
                $pos = strpos(' ' . $this->attributes['class'] . ' ', " $class ");
                if ($pos !== false) {
                    $len = strlen($class);
                    if ($pos > 0 && ' ' === $this->attributes['class'][$pos - 1]) {
                        --$pos;
                        ++$len;
                    }
                    $this->attributes['class'] = trim(substr_replace($this->attributes['class'], '', $pos, $len));
                    if (empty($this->attributes['class'])) {
                        unset($this->attributes['class']);
                        break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Indique si l'attribut class du champ contient l'une des classes indiquées.
     *
     * @param string $class La classe à tester. Vous pouvez également tester
     * plusieurs classes en séparant leurs noms par un espace.
     *
     * Exemple $input->removeClass('text small');
     *
     * Retournera true si l'atttribut class contient la classe 'text' OU la
     * classe 'small'.
     *
     * @return $this
     */
    public function hasClass($class) {
        if (isset($this->attributes['class'])) {
            foreach (explode(' ', $class) as $class) {
                if (false !== strpos(' ' . $this->attributes['class'] . ' ', " $class ")) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retourne ou modifie le nom du champ.
     *
     * @param string $name
     *
     * @return string|$this
     */
    public function name($name = null) {
        if (is_null($name))
            return $this->name;

        $this->name = $name;

        return $this;
    }

    /**
     * Retourne ou modifie le numéro d'occurence du champ.
     *
     * @param int $occurence
     *
     * @return int|$this
     */
    public function occurence($occurence = null) {
        if (is_null($occurence)) {
            return $this->occurence;
        }

        $this->occurence = $occurence;
        $occurence ? $this->addClass('clone') : $this->removeClass('clone');

        return $this;
    }

    /**
     * Retourne le nom du contrôle html pour ce champ.
     *
     * La nom du contrôle est construit à partir du nom du champ, de son numéro
     * d'occurence (s'il est répétable) et du nom des champs parents.
     *
     * Par exemple, si on a un champ "contact" (répétable) qui contient un champ
     * "nom", la méthode retournera une chaine de la forme "contact[0][nom]".
     *
     * @return string
     */
    protected function controlName() {
        $base = $this->parent ? $this->parent->controlName() : '';
        if (!$this->name) return $base;
        $name = $this->name;
        $base && $name = $base . '[' . $name . ']';
        $this->repeatable &&  $name .= '[' . $this->occurence . ']';

        return $name;
    }

    /**
     * Retourne ou modifie le libellé du champ.
     *
     * @param string $label
     *
     * @return string|self
     */
    public function label($label = null) {
        // Getter
        if (is_null($label)) {
            if ($this->label) {
                return $this->label;
            }

            if ($this->schema instanceof FieldInterface) {
                return $this->schema->label();
            }

            return null;
        }

        // Setter
        $this->label = $label;

        return $this;
    }

    /**
     * Retourne ou modifie la description du champ.
     *
     * @param string $description
     *
     * @param null|bool Emplacement de la description par rapport au champ :
     * - false : en haut (valeur par défaut)
     * - true : en bas
     *
     * @return string|$this
     */
    public function description($description = null, $after = null) {
        // Getter
        if (is_null($description)) {
            if ($this->description) {
                return $this->description;
            }

            if ($this->schema instanceof FieldInterface) {
                return $this->schema->description();
            }

            return null;
        }

        // Setter
        $this->description = $description;
        if (!is_null($after)) {
            $this->descriptionAfter = $after;
        }

        return $this;
    }

    /**
     * Retourne ou modifie les données du champ.
     *
     * @param mixed $data
     *
     * @return mixed|$this
     */
    public function data($data = null) {
        if (is_null($data)) {
            return $this->data;
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Retourne ou modifie l'attribut repeatable du champ.
     *
     * @param bool $repeatable
     *
     * @return bool|$this
     */
    public function repeatable($repeatable = null) {
        // Getter
        if (is_null($repeatable)) {
            if ($this->repeatable) {
                return $this->repeatable;
            }

            if ($this->schema instanceof FieldInterface) {
                return $this->schema->repeatable();
            }

            return null;
        }

        // Setter
        $this->repeatable = $repeatable;

        return $this;
    }

    /**
     * Retourne le "niveau de répétition" du noeud en cours.
     *
     * Exemples :
     * - pour un champ non répétable, retourne 0
     * - si le champ est répétable, retourne 1
     * - si le champ est répétable et que son parent est répétable, retoune 2
     * - et ainsi de suite
     *
     * @return int
     */
    protected function repeatLevel() {
        $level = $this->parent ? $this->parent->repeatLevel() : 0;
        $this->repeatable && ++$level;

        return $level;
    }

    public function schema(FieldInterface $schema = null) {
        if (is_null($schema)) {
            return $this->schema;
        }

        $this->schema = $schema;

        return $this;
    }

    /**
     * Initialise les données du champ à partir du tableau (ou de l'objet passé
     * en paramètre).
     *
     * @param array|ArrayAccess|Object|Scalar $data
     */
    public function bind($data) {
        $debug = false;

        if ($this->name) {
            if($debug) echo '&rArr;Field ', $this->type(), '.', $this->name, '::bind()<br />';
            if (is_object($data)) {
                $data = isset($data->{$this->name}) ? $data->{$this->name} : null;
            } else {
                $data = isset($data[$this->name]) ? $data[$this->name] : null;
            }
        } else {
            if($debug) echo '&rArr;Field ', $this->type(), '.&lt;noname&gt;::bind()<br />';
        }

        if ($debug) {
            echo "store ";
            if (is_null($data)) echo "null";
            elseif(is_array($data)) echo empty($data) ? "empty array" : "array";
            elseif(is_object($data)) echo "object of type ", get_class($data);
            elseif (is_scalar($data)) echo gettype($data), ' ', var_export($data, true);
            echo "<br />";
        }
        $this->data = $data;

        return $this;
/*
        $debug = true;

        if($debug) echo $this->type(), '.', $this->name, '::bind(', htmlspecialchars(var_export($data,true)), ')<br />&rArr;';


        // Si le champ n'a pas de nom, aucune liaison possible
        if (! $this->name) {
            if($debug) echo 'name=null, passe tel quel aux enfants<blockquote>';
            $this->data = null;
            if ($this instanceof Fields) {
                foreach ($this->fields as $field) {
                    $field->bind($data); // étage transparent, on passe data aux enfants
                }
            }
            if($debug) echo '</blockquote>';

            return $this;
        }

//        if (! isset($data[$this->name])) {
        if (! ( (is_object($data) ? isset($data->{$this->name}) : isset($data[$this->name])) )) {
            if($debug) echo "name=$this->name, mais data[$this->name] is not set, stocke <code>null</code> et reset de tous les enfants<blockquote>";
            $this->data = null;
            if ($this instanceof Fields) {
                foreach ($this->fields as $field) {
                    $field->bind(null); // reste de tous les enfants
                }
            }
            if($debug) echo '</blockquote>';

            return $this;;
        }

        if($debug) echo "name=$this->name, data[$this->name] is set, stocke <code>", htmlspecialchars(var_export($data[$this->name],true)), "</code> et bind les enfants<blockquote>";
        $this->data = is_object($data) ? $data->{$this->name} : $data[$this->name];
        if ($this instanceof Fields) {
            foreach ($this->fields as $field) {
                $field->bind($this->data); // passe la section aux enfants
            }
        }
        if($debug) echo '</blockquote>';

        return $this;
*/
    }

    public function clear() {
        $this->bind(null);
    }

    /**
     * Indique si la valeur d'une instance unique de ce type de champ est un
     * scalaire ou un tableau. Autrement dit, indique si le champ est multivalué
     * ou non.
     *
     * La majorité des champs sont des champs simples dont la valeur est un
     * scalaire (input text, textarea, etc.)
     *
     * Lorsqu'un champ simple est répétable, il devient multivalué et sa
     * valeur est alors un tableau.
     *
     * Certains champs sont multivalués même lorsqu'ils ne sont pas répétables.
     * C'est le cas par exemple pour une checklist ou un select avec multiple
     * à true. Dans ce cas, le champ est obligatoirement multivalué. (et si
     * jamais il est répétable, alors sa valeur sera un tableau de tableaux).
     *
     * Une container (classe Fields) est toujours considéré comme multivalué :
     * il contient les valeurs de tous les champs qu'il possède.
     *
     * @return bool
     */
    protected function isArray() {
        return $this->repeatable;
    }

    protected function bindOccurence($data) {
        return $this->bind($data);
    }

    public function render($theme = 'default', array $options = array()) {
        // Sanity check
        if (self::$writer) {
            throw new Exception('Rendering already started');
        }

        // Stocke le thème utilisé
        self::$theme = $theme;

        // Crée le XMLWriter
        self::$writer = new XMLWriter();
        self::$writer->openURI('php://output');

        // Options de rendu par défaut
        $options += array(
            'charset' => 'UTF-8',
            'indent' => false,
            'comment' => false,
        );

        // Option 'charset' : jeu de caractère utilisé

        // Pour que XMLWriter nous génère de l'utf-8, il faut obligatoirement
        // appeller startDocument() et indiquer l'encoding. Sinon, xmlwriter
        // génère des entités numériques (par exemple "M&#xE9;nard").
        // Par contre, on ne veut pas que le prologue xml (<?xml ..>) apparaisse
        // dans la sortie générée. Donc on bufferise le prologue et on l'ignore.
        ob_start();
        self::$writer->startDocument('1.0', $options['charset']);
        self::$writer->flush();
        ob_end_clean();

        // Option indent : indentation du code
        self::$indent = (bool)$options['indent'];

        // Le test ci-dessus ignore false, 0 et '' ( = pas d'indentation)
        if (self::$indent) {
            $indent = $options['indent'];

            // true = 4 espaces par défaut
            if ($indent === true) {
                $indent = '    ';

                // entier : n espaces
            } elseif (is_int($indent)) {
                $indent = str_repeat(' ', $indent);
            }

            // Sinon : chaine litérale (tabulation, deux espaces, etc.)

            // Demande au xmlwriter de nous indenter le code
            self::$writer->setIndent(true);
            self::$writer->setIndentString($indent);

        }

        // Option comment
        self::$comment = $options['comment'];

        // Fait le rendu du champ
        $this->block('container');

        // Flushe et ferme le writer
        self::$writer->endDocument();
        self::$writer->flush();
        self::$writer = null;
    }

    /**
     * Exécute un template de rendu pour le champ en cours.
     *
     * Le nom exact du template exécuté est construit à partir du thème en
     * cours (tel que passé lors de l'appel à la méthode render()), du type du
     * champ (tel que retourné par la méthode type()) et du nom de bloc passé
     * en paramétre.
     *
     * Par exemple, block('container') pour un champ Input exécutera le
     * template input.container.php du thème en cours.
     *
     * @param string $block Nom du block à exécuter (container, label, etc.)
     * @param array $args Paramètres à passer au tempalte.
     */
    protected function block($block, array $args = null) {
        $path = $this->findBlock(get_class($this), $block);
        $this->callBlock($path, $args);
    }

    /**
     * Permet à un block "d'hériter" du template de son parent.
     *
     * La méthode fonctionne exactement comme la méthode block(), mais au lieu
     * d'exécuter le bloc du champ en cours, elle exécute le bloc associé à la
     * classe parent du champ en cours.
     *
     * Par exemple, parentBlock('container') pour un champ Input exécutera le
     * template field.container.php du thème en cours (car la classe Input
     * hérite de la classe Field).
     *
     * @param string $block Nom du block à exécuter (container, label, etc.)
     * @param array $args Paramètres à passer au tempalte.
     */
    protected function parentBlock(array $args = null) {
        $last = end($this->callStack);

        $path = $this->findBlock(get_parent_class($last['class']), $last['block']);

        $this->callBlock($path, $args);
    }

    /**
     * Permet à un bloc d'hériter du bloc par défaut fourni par le thème parent.
     *
     * La méthode fonctionne exactement comme la méthode block(), mais au lieu
     * d'exécuter le bloc du thème en cours, elle exécute le bloc présent dans
     * le thème parent du thème en cours.
     *
     * Par exemple, avec le thème bootstrap, defaultBlock('container') pour
     * un champ Input exécutera le template field.container.php du thème default
     * puisque le thème bootstrap hérite du thème default.
     *
     * @param string $block Nom du block à exécuter (container, label, etc.)
     * @param array $args Paramètres à passer au tempalte.
     */
    protected function defaultBlock(array $args = null) {
        if (false === $theme = Themes::parent(self::$theme)) {
            throw new Exception('No parent theme for ' . self::$theme);
        }

        $last = end($this->callStack);

        $path = Themes::path($theme) . $last['file'];
        if (!file_exists($path)) {
            throw new Exception("Default template $theme/$last[file] do not exist");
        }

        $this->callBlock($path, $args);
    }

    /**
     * Exécute le template dont le path est passé en paramètre en lui passant
     * les paramètres indiqués.
     *
     * @param string $path le path complet du template à exécuter.
     * @param array les paramètres à passer au template.
     */
    protected function callBlock($path, array $args = null) {
        $writer = self::$writer;
        if (is_null($args)) {
            $args = array();
        } else {
            extract($args, EXTR_SKIP);
        }

        // Exécute le template et met en commentaire le nom des blocs
        if (self::$comment && false === strpos($path, 'attributes')) {
            $templateFriendlyName = basename(dirname($path)) . '/' . basename($path);
            self::$writer->writeComment(' start ' . $templateFriendlyName);
            include $path;
            self::$writer->writeComment(' end  ' . $templateFriendlyName);
        }

        // Exécute le template normallement
        else {
            include $path;
        }

        // Dépile le bloc ajouté à la pile par findBlock
        array_pop($this->callStack);
    }

    /**
     * Rercherche le template correspondant à un thème, une classe et un nom
     * de cloc donné.
     *
     * La méthode remonte la hiérarchie des classes et des thèmes pour
     * déterminer le path exact du template à exécuter. Si aucun template n'est
     * trouvé, une Exception est levée.
     *
     * @param string $class La classe du champ à partir de laquelle on va
     * commencer à remonter la hiérarchie.
     * @param string $block le nom du bloc recherché.
     *
     * @return string le path absolu du template à exécuter.
     *
     * @throws Exception si aucun template n'a été trouvé.
     */
    protected function findBlock($class, $block) {
        // On remonte la hiérarchie des classes
        do {
            // Détermine le nom du template recherché
            $file = strtolower(substr(strrchr($class, '\\'), 1)) . ".$block.php";

            // Teste s'il existe dans le thème ou dans les thèmes hérités
            if (false !== $path = Themes::search(self::$theme, $file)) {
                // Détection de boucles infinies
                if (isset($this->callStack[$path])) {
                    self::$writer->flush();
                    echo "<pre>Boucle infinie :\n\n";
                    echo implode("\n", array_keys($this->callStack));
                    echo "\n", $path, '</pre>';
                    die();
                }

                // Empile les infos sur le template en cours
                // C'est la méthode callBlock qui se charge de dépiler le bloc
                // après l'exécution de celui-ci.
                $this->callStack[$path] = array(
                    'class' => $class,
                    'file' => $file,
                    'block' => $block,
                );

                return $path;
            }
        } while(false !== $class = get_parent_class($class));

        // Le template demandé n'existe pas dans ce thème
        throw new Exception('Unable to render template');
    }

    public function generateId() {
        if (!isset($this->attributes['id'])) {
            $id = $this->controlName() ? : $this->type();
            if (!isset(self::$usedId[$id])) {
                self::$usedId[$id] = 1;
            } else {
                $id .= ++self::$usedId[$id];
            }

            $this->attributes['id'] = $id;
        }
        return $this->attributes['id'];
    }

    public function toArray($withData = false) {
        $t = array('type' => $this->type());
        foreach ($this as $name => $value) {
            if ($name === 'parent' || $name === 'fields' || $name === 'occurence')
                continue;

            if (is_null($value) || $value === array())
                continue;

            if ($name === 'repeatable' && $value === false)
                continue;

            if ($name === 'data' && !$withData)
                continue;

            $t[$name] = $value;

        }

        if (isset($t['data'])) {
            $data = $t['data'];
            unset($t['data']);
            $t['data'] = $data;
        }

        return $t;
    }

    /**
     * Prépare le formulaire à l'affichage.
     *
     * Cette méthode peut être utilisée pour :
     * - définir les assets dont on va avoir besoin (css, js)
     * - vérifier que le formulaire est valide (pas de boucle, etc.)
     * - déplacer des éléments (par exemple les inputs)
     */
    public function prepare($theme = 'default') {
    }

    /**
     * Retourne les fichiers javascript et css qui sont nécessaires pour
     * afficher et faire fonctionner le formulaire.
     *
     * @return Assets Une collection d'assets.
     */
    public final function assets() {
        // On va faire un parcourt non récursif de l'arbre en utilisant une
        // pile et en élagant lorsqu'on rencontre un type de champ déjà vu.

        // Pile contenant les noeuds à visiter
        $stack = array($this);

        // Liste des types de noeud qu'on a déjà vu, pour l'élagage
        $seen = array();

        // Commence avec les assets définis par le thème
        $assets = new Assets;

        // Tant qu'y'a de la pile ;-)
        while ($stack) {
            // Récupère le prochain champ à aller voir
            $field = array_shift($stack);

            // Liste de champs, il faudra visiter les enfants, ajoute à la pile
            if ($field instanceof Fields) {
                $stack = array_merge($stack, $field->fields);
            }

            // Type de champ qu'on n'a pas encore vu, on lui demande ses assets
            $type = get_class($field);
            if (!isset($seen[$type])) {
                $seen[$type] = true;
                if ($a = $field::getAssets()) {
                    $assets->add($a);
                }
            }
        }

        return $assets;
    }

    /**
     * Retourne les fichiers javascript et css qui sont nécessaires pour
     * ce type de champ.
     *
     * Remarque : ne pas confondre cette méthode (getAssets) avec la méthode
     * assets() :
     * - assets() se charge de créer la liste de tous les assets requis pour
     *   faire le rendu de l'ensemble du formulaire. Elle parcourt tous les
     *   champs, appelle getAssets(), dédoublonne les assets, etc. De ce fait,
     *   elle est marquée "final" car elle ne doit pas être surchargée par les
     *   classes filles et elle est publique car elle fait partie de l'API.
     * - getAssets() ne s'occuppe que des assets requis pour un type de champ
     *   donné. Elle est statique, car les assets ne dépendent que du type de
     *   champ, pas de ses paramètres et elle est protected car c'est notre
     *   cuisine interne. Dans les classes filles, c'est cette méthode qu'il
     *   faut surcharger pour déclarer des assets.
     *
     * @return null|array La méthode doit retourner soit null (si ce type de
     * champs n'a besoin de rien), soit un tableau d'assets, c'est-à-dire un
     * tableau de tableaux.
     *
     * Chaque asset peut contenir les éléments suivants :
     * - type : obligatoire, soit css, doit js.
     * - name : optionnel, le nom de l'asset (jquery, bootstrap-css, etc.)
     * - src  : optionnel, l'url de l'asset
     * - version : optionnel, le numéro de version de l'asset
     * - position : optionnel, soit top soit bottom
     * - media : optionnel, uniquement pour les css, le media (all, screen etc.)
     *
     * Remarque : name et src sont optionnels mais l'un des deux doit être
     * fourni.
     *
     * Important : dans la méthode assets(), aucun contrôle n'est fait pour
     * vérifier la validité de l'asset. Vous devez donc vous assurer que le
     * tableau que vous retournez respecte bien les spécifications ci-dessus.
     */
    protected static function getAssets() {
    }

}
