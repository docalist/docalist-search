<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2016-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests\Biblio\UserData
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Tests;

use WP_UnitTestCase;
use Docalist\Search\Mapping;

class MappingTest extends WP_UnitTestCase
{

    public function testOne()
    {
        // CustomPostTypeIndexer

        $mapping = new Mapping('test', 'fr-text');

        $mapping->keyword('in');
        $mapping->keyword('type');
        $mapping->keyword('status');
        $mapping->text('slug');
        $mapping->keyword('createdby');
        $mapping->date('creation');
        $mapping->date('lastupdate');
        $mapping->text('title');
        $mapping->keyword('title-sort');
        $mapping->text('content');
        $mapping->text('excerpt');
        $mapping->keyword('tag');
        $mapping->keyword('category');

        var_dump($mapping->getMapping());

        // Organization
        // Name
        $mapping->text('name')->suggest()
                ->text('name-*')->suggest()->copyDataTo('name');
        return;
        // Date
        $mapping->addField('date')->date()
        ->addTemplate('date-*')->copyFrom('date')->copyDataTo('date');

        // Content
        $mapping->addField('content')->text()
        ->addTemplate('content-*')->copyFrom('content')->copyDataTo('content');

        // Topic
        $mapping->addField('topic')->text()->filter()->suggest()
        ->addTemplate('topic-*')->copyFrom('topic')->copyDataTo('topic');

        // Organization
        $mapping->addField('organization')->integer()
        ->addTemplate('organization-*')->copyFrom('organization')->copyDataTo('organization');

        // Person
        $mapping->addField('person')->integer()
        ->addTemplate('person-*')->copyFrom('person')->copyDataTo('person');

        // Address

        // Phone

        // Figures
        $mapping->addField('figures')->decimal()
        ->addTemplate('figures-*')->copyFrom('figures')->copyDataTo('figures');

        // Number
        $mapping->addField('number')->literal()
        ->addTemplate('number-*')->copyFrom('number')->copyDataTo('number');

        // Link
        $mapping->addField('link')->url()
        ->addTemplate('link-*')->copyFrom('link')->copyDataTo('link');

    }
}
