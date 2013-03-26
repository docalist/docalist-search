<?php
namespace Docalist\Search;
use WP_Widget;

class FacetsWidget extends WP_Widget {
        // widget actual processes
    public function __construct() {
        parent::__construct(
            'Docalist Search Facets', // Base ID
            'Facets', // Name
            array( // Args
                'description' => __('A Foo Widget', 'text_domain'),
            )
        );
    }

    public function widget($args, $instance) {
        // Liste des facets qu'on veut avoir
        if (! \is_search()) {
            return;
        }

        // cf http://www.elasticsearch.org/guide/reference/api/search/facets/terms-facet.html
        $facets = array(
            'Type de document' => array(
                'type' => 'terms',
                'field' => 'type.keyword',
            ),
/*
            'Genre de document' => array(
                'type' => 'terms',
                'field' => 'genre',
            ),
 */
            'Journal' => array(
                'type' => 'terms',
                'field' => 'journal.keyword',
            ),

            'Auteurs' => array(
                'type' => 'terms',
                'script_field' => '_source.author',
//                'all_terms'=>true,
//                'nested' => 'author',
//                'size'=>20,
            ),

            'Mots-clés' => array(
                'type' => 'terms',
                'field' => 'topic.term.keyword',
//                'nested' => 'topics',
            ),
        );

        $result = \Docalist::get('docalist-search')->facets($facets);

        // Affiche les facettes
        foreach ($result as $name => $facet) {
            echo "<h3>$name</h3>";
            echo '<ul>';
            $field = $facets[$name]['field'];
            foreach($facet->terms as $term) {
                $url = "?s=$field:" . $term->term;
                printf('<li><a href="%s">%s</a> (%d)</li>', $url, $term->term, $term->count);
            }
            echo '</ul>';
        }
    }

    public function form($instance) {
        echo "FORM DE FACETSWIDGET<br />";
        // outputs the options form on admin
    }

    public function update($new_instance, $old_instance) {
        echo "UPDATE DE FACETSWIDGET<br />";
        // processes widget options to be saved
    }
}
