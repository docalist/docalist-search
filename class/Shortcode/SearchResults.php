<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Shortcode;

use Docalist\Search\Shortcode;
use Docalist\Search\SearchUrl;
use Docalist\Search\SearchRequest;
use Docalist\Search\SearchResponse;
use WP_Query;
use Docalist\Views;

/**
 * Shortcode "Liste de réponses".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class SearchResults implements Shortcode
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultAttributes(): array
    {
        return [
            'more' => __('Voir tout »', 'docalist-search'),
            'no-results' => __('Rien à afficher...', 'docalist-search'),
            'template' => 'title',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function render(array $attributes, string $content): string
    {
        // Récupère les paramètres du shortcode
        $attributes = array_filter($attributes, 'strlen'); // supprime les attributs vides
        $attributes += $this->getDefaultAttributes();
        $url = $content;
        if (empty($url)) {
            return __('Aucune url de recherche indiquée.', 'docalist-search');
        }

        // Lance une recherche docalist-search
        $searchResponse = $this->getSearchResponse($url);
        if (is_null($searchResponse)) {
            return __("Une erreur s'est produite pendant la recherche.", 'docalist-search');
        }

        // Crée une WPQuery contenant les posts correspondants
        $query = $this->getQuery($searchResponse);

        // Pour permettre aux templates d'itérer en utilisant have_post() et the_post() qui utilisent la variable
        // globale $wp_query, on remplace (temporairement) la requête globale par la requête qu'on a créée.
        $save = $GLOBALS['wp_query'];
        $GLOBALS['wp_query'] = $query;

        // Détermine le template à utiliser
        $result = '';
        $template = $this->getTemplate($attributes['template']);
        if (empty($template)) {
            if (current_user_can('manage_options')) {
                $result = sprintf(
                    __(
                        "<p>Note aux admins : le template <code>'%s'</code> indiqué dans le shortcode
                        n'existe pas, utilisation du template par défaut <code>'title'</code>.</p>",
                        'docalist-search'
                    ),
                    $attributes['template']
                );
            }
            $attributes['template'] = 'title';
            $template = $this->getTemplate($attributes['template']);
        }

        // Exécute le template
        $result .= (function () use ($attributes, $url, $template) {
            ob_start();
            include $template;
            return ob_get_clean();
        })();

        // Restaure la requête globale de WordPress
        $GLOBALS['wp_query'] = $save;
        wp_reset_postdata();

        // Retourne le résultat du shortcode
        return $result;
    }

    /**
     * Retourne le path du template indiqué en paramêtre.
     *
     * La méthode accepte soit le nom de code d'un template prédéfini ('title', 'excerpt' ou 'content'),
     * soit le path relatif d'un template présent dans le thème en cours (par exemple '/partials/posts.php').
     *
     * @param string $template
     *
     * @return string Retourne le path du template ou une chaine vide si le template indiqué n'existe pas.
     */
    private function getTemplate(string $template): string
    {
        // Teste s'il s'agit d'un template prédéfini
        switch ($template) {
            case 'title':
            case 'excerpt':
            case 'content':
                $views = docalist('views'); /** @var Views $views */
                return $views->getPath('docalist-search:shortcode/docalist-search-results');
        }

        // Il s'agit d'un template dans le thème en cours
        return locate_template($template);
    }

    /**
     * Exécute l'url de recherche passée en paramètre et retourne l'objet SearchResponse obtenu.
     *
     * @param string $url
     *
     * @return SearchResponse|null
     */
    private function getSearchResponse(string $url): ?SearchResponse
    {
        $searchUrl = new SearchUrl($url);
        $searchRequest = $searchUrl->getSearchRequest();

        return $searchRequest->execute();
    }

    /**
     * Crée une requête WordPress qui contient les posts obtenues dans la SearchResponse passée en paramètre.
     *
     * @param SearchResponse $searchResponse
     *
     * @return WP_Query
     */
    private function getQuery(SearchResponse $searchResponse): WP_Query
    {
        $ids = [];
        foreach ($searchResponse->getHits() as $hit) {
            $ids[] = $hit->_id;
        }

        empty($ids) && $ids = [0]; // c'est la syntaxe pour dire à wp "ne chercher aucun post"

        $args = [
            // post_type:any ne fonctionne pas car les bases docalist sont déclarées avec exclude_from_search:true
            'post_type' => $searchResponse->getSearchRequest()->getTypes(),
            'ignore_sticky_posts' => true,
            'post__in' => $ids,
            'orderby'   => 'post__in',
            'nopaging' => true,
            'post_status' => 'any',
            'no_found_rows' => true,
        ];

        return new WP_Query($args);
    }
}
