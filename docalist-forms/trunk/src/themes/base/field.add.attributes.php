<?php
// Génère les attributs du bouton "ajouter" du champ répétable en cours.
//
// Quand ce template est exécuté, l'attribut type="button" a déjà
// été généré.
//
// Ce template (et les templates qui le surchargent) doivent générer :
//
// - Un attribut class="cloner" (et éventuellement des classes en plus)
//
// - Un attribut "data-clone" (cf doc) permettant à la librairie
//   javascript de savoir quel élément cloner lorsque le bouton
//   "ajouter" sera cliqué (cf default/field.add.php).
//
//   Par défaut, c'est l'élément qui précède immédiatement le bouton,
//   donc il ne faut générer l'attribut data-clone que s'il faut
//   désigner un autre élément à cloner.
//
// - Un attribut "data-level" qui indique à quel niveau de répétition
//   on se trouve pour que les champs du clone soient renommés
//   correctement lors du clonage (cf doc).
//
//   La valeur par défaut est 1 donc il ne faut générer l'attribut
//   que si on est à une plus grande profondeur.
//
// - Eventuellement un attribut title (bulle d'aide)

// Génère la classe "cloner" (obligatoire)
// Les thèmes descendants peuvent nous appeller en passant un argument
// "class" indiquant les classes supplémentaires à ajouter au bouton.
$class = isset($class) ? ($class . ' cloner') : 'cloner';
$writer->writeAttribute('class', $class);

// Génère data-clone (si différent de '<')
// Les thèmes descendants peuvent nous appeller en passant un argument
// "data-clone" indiquant le sélecteur à générer
if (isset($args['data-clone']) && $args['data-clone'] !== '<') {
    $writer->writeAttribute('data-clone', $args['data-clone']);
}

// Génère data-level (si différent de 1)
$repeatLevel = $this->repeatLevel();
$repeatLevel > 1 && $writer->writeAttribute('data-level', $repeatLevel);

// Génère une bulle d'aide (optionnel)
$writer->writeAttribute('title', 'Ajouter ' . lcfirst($this->label()));


