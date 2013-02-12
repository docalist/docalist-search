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

$(document).ready(function(){

    /**
     * Clone un champ simple (un input, un select, une checklist, etc.)
     */
    $('.btn-add-field').click(function(e){
        var btn = $(e.currentTarget);
        console.log('Click sur le bouton ', btn);

        // Détermine l'élément à cloner
        var item = btn.prev();
        console.log('champ à cloner : ', item);

        // Clone l'élément
        var clone = item.clone();

        var re = /\[(\d+)\](?!.*\[\d+\])/;
        /*
         * La regexp ci-dessus capture le dernier nombre entre crochets.
         *
         * Elle se lit de la façon suivante : "rechercher un nombre entre
         * crochets qui n'est pas suivi par un nombre entre crochets".
         *
         * \[(\d+)\] : un nombre entre cochets (on ne capture que le nombre)
         * (?! xxx)  : negative lookahead (qui n'est pas suivi par)
         * xxx = n'importe quoi (.*) suivi d'un nombre entre crochets \[\d+\].
         */
        $(':input,label', clone).andSelf().each(function(){
            var input = $(this);
            input.val('').attr('selected', false).attr('checked', false);

            $.each(['name', 'id', 'for'], function(i, name){
                var value = input.attr(name);
                if (! value) return;
                var old=value;
                value = value.replace(re, function(str, i) {
                    console.log(str, i);
                    return '[' + (parseInt(i) + 1) + ']';
                });
                input.attr(name, value);
                console.log("Renommage", name, ':', old, '->', value);
            });
        });

        // Insère le clone juste après l'élément
        item.after(' ', clone);

        // Met le focus sur le clone
        clone.focus();
    });

    $('.OLDbtn-add-field').click(function(e){
        var btn = $(e.currentTarget);
        console.log('Click sur le bouton ', btn);
        // Détermine l'élément à cloner
        var item;
        var parent = btn.parent()[0];
        console.log('parent du bouton : ', parent);
        switch (btn.parent()[0].tagName)
        {
            case 'LI': // Le dernier LI contient le bouton repeat. Sélectionne le LI qui précède.
                item = btn.closest('li').prev()
                break;

//            case 'TD': // Sélectionne le dernier TR du bloc TBODY de la TABLE qui contient le bouton repeat.
//                item = $('tbody tr:last', btn.closest('table'))
//                break;

            default: // Sélectionne l'élément qui précède le bouton repeat
                item = btn.prev();
        }
        console.log('item à cloner : ', item);
        // Clone l'élément
        var clone = item.clone();

        // Réinitialise tous les contrôles qui figurent dans le clone et modifie leur nom
        $(':input,label', clone).each(function(){
            var input = $(this);
            input.val('').attr('selected', false).attr('checked', false);

            $.each(['name', 'id', 'for'], function(i, name){
                var value = input.attr(name);
                if (! value) return;
                var old=value;
                value = value.replace(/\[(.*?)\]/, function(str, i) {
                    return '[' + (parseInt(i) + 1) + ']';
                });
                input.attr(name, value);
                console.log("Renommage", name, ':', old, '->', value);
            });
        });

        // Insère le clone juste après l'élément
        clone.insertAfter(item);
    });
});