<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;

/**
 * Un indexeur qui ne fait rien.
 *
 * Cette classe sert à remplacer un indexeur manquant ou invalide (cf. la
 * méthode Indexer::indexer()).
 *
 * Quand on désactive un plugin, on peut se retrouver avec un contenu indexé
 * pour lequel l'indexeur n'est plus disponible (par exemple, on a indexé
 * une base documentaire puis on désactive docalist-biblio). Cette classe sert
 * à gérer ce cas : au lieu de générer une erreur fatale (c'est ce que ça
 * faisait avant), l'indexeur manquant est remplacé par un NullIndexer et une
 * notice warning est générée.
 *
 */
class NullIndexer extends TypeIndexer {

    public function __construct() {
        parent::__construct('null');
    }

    public function realtime() {
        return;
    }

    public function contentId($content) {
        return 0;
    }

    public function map($content) {
        return [];
    }

    public function indexAll(Indexer $indexer) {
        return;
    }
}