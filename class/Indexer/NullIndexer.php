<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Indexer;

use Docalist\Search\Indexer\AbstractIndexer;
use Docalist\Search\IndexManager;

/**
 * Un indexeur qui ne fait rien.
 *
 * Cette classe sert à remplacer un indexeur manquant ou invalide (cf. la méthode IndexManager::getIndexer()).
 *
 * Quand on désactive un plugin, on peut se retrouver avec un contenu indexé pour lequel l'indexeur n'est plus
 * disponible (par exemple, on a indexé une base documentaire puis on désactive docalist-biblio). Cette classe sert
 * à gérer ce cas : au lieu de générer une erreur fatale, l'indexeur manquant est remplacé par un NullIndexer et une
 * admin notice est générée.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class NullIndexer extends AbstractIndexer
{
    public function getType()
    {
        return 'null';
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
