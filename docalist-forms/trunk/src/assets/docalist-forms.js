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

$(document).ready(function() {

    /**
     * Génère un effet highlight sur l'élément passé en paramètre : l'élément
     * apparaît en jaune clair quelques instants puis s'estompe.
     *
     * On gère l'effet nous-même pour éviter d'avoir une dépendance envers
     * jquery-ui.
     *
     * @param DomElement el l'élément à highlighter
     */
    function highlight(el) {
        // Principe : on crée une div jaune avec exactment les mêmes dimensions
        // que l'élément et on la place au dessus en absolute puis on l'estompe
        // (fadeout) gentiment avant de la supprimer.
        // Adapté de : http://goo.gl/9pr4Q
        $("<div/>").width(el.outerWidth()).height(el.outerHeight()).css({
            'position' : 'absolute',
            'left' : el.offset().left,
            'top' : el.offset().top,
            'background-color' : '#ffff99',
            'opacity' : '0.5',
            'z-index' : '9999999'
        }).appendTo('body').fadeOut(500, function() {
            $(this).remove();
        });
    }

    /**
     * Gère les boutons de répétition des champs.
     *
     * Les boutons doivent avoir la classe "cloner" et peuvent avoir un attribut
     * "data-clone" qui indique l'élément à cloner (cf doc).
     */
    $('body').on('click', '.cloner', function() {
        // On va travailler en plusieurs étapes :
        //
        // 1. déterminer l'élément à cloner à partir du bouton qui a été cliqué
        // 2. cloner cet élément
        // 3. enlever du clone les sous-éléments qui sont eux-même des clones
        // 4. renommer les attributs name, id, for, etc.
        // 5. réinitialiser les champs du clone à vide
        // 6. insérer le clone au bon endroit
        // 7. highlighte le nouveau champ et lui donne le focus

        // 1. Détermine le bouton qui a été cliqué
        var node = $(this);

        // Récupère le sélecteur à appliquer (attribut data-clone, '<' par défaut)
        var selector = node.data('clone') || '<';

        // Exécute les commandes contenues dans le préfixe (< = prev, ^ = parent)
        for (var i = 0; i < selector.length; i++) {
            switch (selector.substr(i, 1)) {
                // parent
                case '^':
                    node = node.parent();
                    continue;

                // previous
                case '<':
                    node = node.prev();
                    continue;
            }

            // Autre caractère = début du sélecteur jquery
            break;
        }

        // On extrait ce qui reste du sélecteur et on l'applique (si non vide)
        selector = selector.substr(i);
        if (selector.length) {
            node = $(selector, node);
        }

        // node pointe maintenant sur le noeud à cloner
        console.log('noeud à cloner : ', node.eq(0));

        // 2. Clone le noeud
        var clone = node.clone();

        // 3. Exclue du clone les éléments qui sont eux-mêmes des clones
        $('.clone', clone).remove();

        // Ajoute la classe 'clone' au noeud pour qu'on sache que c'est un clone
        clone.addClass('clone');

        // 4. Renomme les champs

        // Récupère le repeatLevel() en cours
        var level = parseInt($(this).data('level')) || 1; // NaN ou 0 -> 1

        // Incrémente le level-ième nombre entre crochets trouvé dans les
        // attributs name, for et id de tous les champs et labels
        $(':input,label', clone).andSelf().each(function(){
            var input = $(this);

            $.each(['name', 'id', 'for'], function(i, name){
                var value = input.attr(name); // valeur de l'attribut name, id ou for
                if (! value) return;
                var old = value;
                var curLevel = 0;
                console.log('renommage de ', value, 'level=', level);
                value = value.replace(/\[(\d+)\]/g, function(match, i) {
                    console.log('match=', match, 'i=', i, 'curlevel=', curLevel);
                    if (++curLevel !== level) return match;
                    return '[' + (parseInt(i)+1) + ']';
                });
                input.attr(name, value);
                console.log("Renommage", name, ':', old, '->', value);
            });
        });

        // 5. Fait un clear sur tous les champs présents dans le clone
        // Source : http://goo.gl/RE9f1
        clone.find('input:text, input:password, input:file, select, textarea').val('');
        clone.find('input:radio, input:checkbox').removeAttr('checked').removeAttr('selected');

        // Si on voulait faire un reset, j'ai trouvé la méthode suivante :
        // var form = $('<form>').append(clone).get(0).reset();
        // pb : si on édite une notice, on récupère dans le clone les valeurs
        // déjà saisies.

        // 6. Insère le clone juste après le noeud d'origine, avec un espace entre deux
        node.after(' ', clone);

        // 7. Fait flasher le clone pour que l'utilisateur voit l'élément inséré
        highlight(clone);

        // Donne le focus au premier champ trouvé dans le clone
        clone.is(':input') ? clone.focus() : $(':input:first', clone).focus();
    });
});
