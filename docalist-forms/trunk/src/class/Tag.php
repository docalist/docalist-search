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
 * @version     SVN: $Id$
 */

namespace Docalist\Forms;

use Exception;

/**
 * Un tag (p, div, etc.) au sein d'un formulaire.
 */
class Tag extends Fields {
    /**
     * @var string Le tag de l'élément.
     */
    protected $tag;

    /**
     * @var string Le contenu textuel de l'élément.
     */
    protected $content;

    /**
     * Crée un nouveau tag.
     *
     * @param string $tag le tag de l'élément.
     *
     * @param string $content Le contenu de l'élément.
     */
    public function __construct($tag, $content = null) {
        $this->parseTag($tag);
        $this->content = $content;
    }

    /**
     * @inheritdoc
     *
     * Un champ de type "Tag" ne peut pas avoir de label.
     */
    public function label($label = null) {
        if (is_null($label))
            return $this->label;

        throw new Exception('a Tag can\'t have a label');
    }

    /**
     * Retourne ou modifie le tag de l'élément.
     *
     * @param string $tag Le nouveau tag de l'élément.
     *
     * La chaine passée en paramètre peut également contenir un nom, un id et
     * des noms de classe (dans cet ordre). Par exemple si vous passez la chaine
     * <code>'input[age]#age.date.required'</code>, cela créera un élément de la
     * forme
     * <code><input name="age" id="age" class="date required" /></code>
     *
     * @return string|$this
     */
    public function tagname($tag = null) {
        if (is_null($tag))
            return $this->tag;

        $this->parseTag($tag);

        return $this;
    }

    /**
     * Retourne ou modifie le contenu textuel de l'élément.
     *
     * @param string $content
     *
     * @return string|$this
     */
    public function content($content = null) {
        if (is_null($content))
            return $this->content;

        $this->content = $content;

        return $this;
    }

    /**
     * Analyse un sélecteur de la forme tag[name]#id.class.class.
     *
     * Formes possibles :
     * - tag (un tag, pas de nom, ni d'id ni de classes),
     * - tag[name] (un tag, un nom, ni d'id ni de classes),
     * - tag#id (un tag, un id, pas de classes),
     * - tag.class1.class2 (un tag, des classes pas d'id),
     * - tag#id.class1.class2 (un tag, un id et des classes).
     *
     * id et classes doivent apparaitre dans le bon ordre (#id.class et non
     * pas class#id)
     */
    protected function parseTag($tag) {
        //@formatter:off
        $re =
            '~^
            (                       # Obligatoire : tag
                [a-z0-9-]+             # $1=tag
            )
            (?:                     # Optionnel : nom entre crochets
                \[                  # crochet ouvrant
                    ([a-z-]+)       # $2=name
                \]                  # crochet fermant
            )?
            (?:                     # Optionnel : id
                \#                  # Précédé du signe dièse
                ([a-z-]+)           # $3=id
            )?
            (?:                     # Optionnel : une ou plusieurs classes
                \.                  # Commence par un point
                ([a-z\.-]+)         # $4=toutes les classes
            )*
            ($)                     # capture bidon, garantit tout de $1 à $4
            ~ix';
        //@formatter:off

        if (! preg_match($re, $tag, $matches)) {
            throw new Exception("Incorrect tag: $tag");
        }

        list($subject, $this->tag, $name, $id, $class) = $matches;

        $name && $this->attributes['name'] = $name;
        $id && $this->attributes['id'] = $id;
        $class && $this->attributes['class'] = strtr($class, '.', ' ');
    }

}
