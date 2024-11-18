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

namespace Docalist\Search\Extension;

use Docalist\AdminNotices;
use Docalist\Container\ContainerBuilderInterface;
use Docalist\Container\ContainerInterface;
use Docalist\Kernel\KernelExtension;
use Docalist\Lookup\LookupManager;
use Docalist\Repository\SettingsRepository;
use Docalist\Search\ElasticSearchClient;
use Docalist\Search\IndexManager;
use Docalist\Search\Lookup\IndexLookup;
use Docalist\Search\Lookup\SearchLookup;
use Docalist\Search\MappingBuilder;
use Docalist\Search\MappingBuilder\ElasticsearchMappingBuilder;
use Docalist\Search\DocalistSearchPlugin;
use Docalist\Search\QueryDSL;
use Docalist\Search\QueryParser\ExplainBuilder;
use Docalist\Search\QueryParser\Lexer;
use Docalist\Search\QueryParser\Parser;
use Docalist\Search\QueryParser\QueryBuilder;
use Docalist\Search\SearchAttributes;
use Docalist\Search\SearchEngine;
use Docalist\Search\Settings;
use Docalist\Search\SettingsPage;
use Docalist\Search\Widget\DisplayAggregations;
use Docalist\Views;
use Exception;

final class DocalistSearchExtension extends KernelExtension
{
    public function build(ContainerBuilderInterface $containerBuilder): void
    {
        $containerBuilder

        // //////////////////////////////////// CONFIGURATION //////////////////////////////////////

        // Ajoute nos vues au service "views"
        ->listen(Views::class, static function (Views $views): void {
            $views->addDirectory('docalist-search', DOCALIST_SEARCH_DIR.'/views');
        })

        // Définit les lookups de type "index" et "search"
        ->listen(LookupManager::class, static function (LookupManager $lookupManager, ContainerInterface $container): void {
            $lookupManager->setLookupService('index', $container->get(IndexLookup::class));
            $lookupManager->setLookupService('search', $container->get(SearchLookup::class));
        })

        // ////////////////////////////////////// PARAMETRES ///////////////////////////////////////

        ->set('elasticsearch-version', function (ContainerInterface $container): string {
            $version = $container->get(Settings::class)->esversion->getPhpValue();
            if (empty($version) || $version === '0.0.0') {
                throw new Exception('Elasticsearch version is not available, check settings.');
            }

            return $version;
        })
        ->alias('elasticsearchVersion', 'elasticsearch-version')

        // /////////////////////////////////////// SERVICES ////////////////////////////////////////

        // Configuration du plugin
        ->set(Settings::class, [SettingsRepository::class])

        // Client Elasticsearch
        ->set(ElasticSearchClient::class, [Settings::class])
        ->deprecate('elasticsearch', ElasticSearchClient::class, '2023-11-28')
        ->deprecate('elastic-search', ElasticSearchClient::class, '0.12')

        // Query DSL
        ->set(QueryDSL::class, function (ContainerInterface $container): QueryDSL {
            $version = $container->string('elasticsearch-version');
            if ($version >= '5.0.0') {
                return new QueryDSL\Version500();
            } elseif ($version >= '2.0.0') {
                return new QueryDSL\Version200();
            } else {
                return new QueryDSL\Version200();
            }
        })
        ->deprecate('elasticsearch-query-dsl', QueryDSL::class, '2023-11-28')

        // Mapping Builder
        ->set(MappingBuilder::class, fn (ContainerInterface $container): MappingBuilder => new ElasticsearchMappingBuilder(
            $container->string('elasticsearch-version'),
            DOCALIST_SEARCH_VERSION
        ))
        ->deprecate('mapping-builder', MappingBuilder::class, '2023-11-28')

        // Index Manager
        ->set(IndexManager::class, [Settings::class, ElasticSearchClient::class, AdminNotices::class])
        ->deprecate('docalist-search-index-manager', IndexManager::class, '2023-11-29')

        // Search Engine
        ->set(SearchEngine::class, [Settings::class])
        ->deprecate('docalist-search-engine', SearchEngine::class, '2023-11-29')

        // Search Attributes
        ->set(SearchAttributes::class, function (ContainerInterface $container): SearchAttributes {
            return $container->get(IndexManager::class)->getSearchAttributes();
        })
        ->deprecate('docalist-search-attributes', SearchAttributes::class, '2023-11-29')

        // Index Lookup
        ->set(IndexLookup::class, ['elasticsearch-version'])
        ->deprecate('index-lookup', IndexLookup::class, '2023-11-29')

        // Search Lookup
        ->set(SearchLookup::class, [ElasticSearchClient::class])
        ->deprecate('search-lookup', SearchLookup::class, '2023-11-29')

        // Query Parser
        ->set(Lexer::class)

        ->set(QueryBuilder::class, fn (ContainerInterface $container): QueryBuilder => new QueryBuilder(
            $container->get(QueryDSL::class),
            $container->get(Settings::class)->getDefaultSearchFields()
        ))
        ->set(ExplainBuilder::class)

        ->set(Parser::class, [Lexer::class, QueryBuilder::class, ExplainBuilder::class])
        ->deprecate('query-parser', SearchLookup::class, '2023-11-29')

        // Settings page
        ->set(SettingsPage::class, [
            Settings::class,
            ElasticSearchClient::class,
            IndexManager::class,
            SearchAttributes::class,
            AdminNotices::class,
        ])

        // Plugin (ContainerAware)
        ->set(DocalistSearchPlugin::class, fn (ContainerInterface $container): DocalistSearchPlugin => new DocalistSearchPlugin(
            $container
        ))
        ->deprecate('docalist-search', DocalistSearchPlugin::class, '2023-11-27')

        // Widgets
        //->set(FacetsWidget::class, fn (ContainerInterface $container): FacetsWidget => new FacetsWidget()) // todo : vérifier et supprimer
        ->set(DisplayAggregations::class, [SearchEngine::class])

        ;
    }
}
