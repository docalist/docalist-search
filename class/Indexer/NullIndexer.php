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
 */
namespace Docalist\Search\Indexer;

use Docalist\Search\Indexer\AbstractIndexer;
use Docalist\Search\IndexManager;

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
 * admin notice est générée.
 */
class NullIndexer extends AbstractIndexer
{
    public function getType()
    {
        return 'null';
    }

    public function activeRealtime()
    {
        return;
    }

    public function getID($content)
    {
        return 0;
    }

    public function map($content)
    {
        return [];
    }

    public function indexAll(IndexManager $indexManager)
    {
        return;
    }
}