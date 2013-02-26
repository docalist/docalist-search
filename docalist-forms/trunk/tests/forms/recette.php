<?php
use Docalist\Forms\Form;


$form = new Form();
$form ->label('Un formulaire de saisie pour des recettes de cuisine')
      ->description('Ce formulaire est inspiré de <a href="http://www.marmiton.org/recettes/recette_lasagnes-de-haute-corse_48084.aspx">cette recette</a>.');

// -----------------------------------------------------------------------------

$form->input('titre')
     ->label('Nom de la recette')
     ->addClass('input-block-level')
     ->description('Essayez de donner un titre explicite à votre recette : ça doit faire envie !');

$form->select('type')
     ->label('Type de plat')
     ->options(array('Entrée froide', 'Entrée chaude', 'Soupe', 'Plat principal', 'Dessert', 'Astuce'));

$form->select('difficulte')
     ->label('Difficulté')
     ->options(array('Très facile', 'Facile', 'Moyenne', 'Difficile'));

$form->select('cout')
     ->label('Coût')
     ->options(array('Bon marché', 'Moyen', 'Cher', 'Honteusement luxueux'));

$form->checkbox('vegetarien')
     ->label('Végétarien')
     ->description('Cochez cette case si votre recette est compatible avec un régime végétarien.');

$form->table('temps')
     ->label('Temps de préparation')
     ->repeatable(true)
     ->description('Indiquez les différentes étapes de préparation et la durée (en minutes) de chaque étape.', false)
        ->select('type')
        ->label('Etape')
        ->addClass('input-block-level')
        ->options(array('Temps de préparation', 'Temps de repos', 'Temps de cuisson', 'Temps de glaçage', 'Durée totale'))
     ->parent()
        ->input('duree')
        ->label('Durée (en minutes)')
        ->addClass('input-mini');

$form->input('quantite')
     ->label('Quantité')
     ->addClass('input-block-level')
     ->description('Exemple : pour 4 personnes, pour 10 pièces, pour un litre, etc.');

$form->fieldset('Liste d\'ingrédients')
     ->name('ingredients')
     ->repeatable(true)
        ->input('part')
        ->label('Partie de la recette')
        ->addClass('input-block-level')
     ->parent()
        ->table('liste')
        ->label('Ingrédients')
        ->description('Listez <strong>tous</strong> les ingrédients nécessaires à la réalisation de votre recette. Vous pouvez ajouter une nouvelle liste d\'ingrédients avec le bouton ci-dessous.', true)
        ->repeatable(true)
             ->input('nom')
             ->label('Nom de l\'ingrédient')
             ->addClass('input-block-level')
         ->parent()
             ->input('quantite')
             ->label('Quantité ')
             ->addClass('input-medium')
         ->parent()
             ->input('remarque')
             ->label('Remarque (qualité, alternative...)')
             ->addClass('input-block-level')
         ;

$form->table('preparation')
     ->label('Préparation')
     ->repeatable(true)
     ->description('Listez les différentes étapes de la préparation', false)
         ->input('step')
         ->label('Nom de cette étape')
         ->addClass('input-block-level')
     ->parent()
         ->textarea('description')
         ->label('Description')
         ->addClass('input-block-level')
         ;

$form->submit('Go !');


$lasagnes = array (
    'titre' => 'Lasagnes de Haute-Corse',
    'type' => 'Plat principal',
    'difficulte' => 'Moyenne',
    'cout' => 'Cher',
    'temps' => array (
        array('type' => 'Temps de préparation', 'duree' => 120),
        array('type' => 'Temps de cuisson', 'duree' => 75),
    ),
    'quantite'   => 'Pour 6 personnes',
    'ingredients'=> array (
        array('part' => 'Pour la garniture', 'liste' => array(
            array('nom' => 'Poulet', 'quantite' => 1),
            array('nom' => 'Boeuf','quantite' => '500 g', 'remarque' => '(du boeuf, pas du cheval...)'),
            array('nom' => 'Veau', 'quantite' => '500 g'),
            array('nom' => 'Oignon', 'quantite' => 1),
            array('nom' => 'Ail', 'quantite' => '2 gousses'),
            array('nom' => 'Coulis','quantite' => '2 cuillères à soupe'),
            array('nom' => 'Vin rouge', 'quantite' => '20 cl'),
            array('nom' => 'Eau'),
            array('nom' => 'Sel'),
            array('nom' => 'Poivre'),
            array('nom' => 'Thym'),
            array('nom' => 'Parmesan', 'remarque' => 'Plutôt du parmesan fort.'),
        )),
        array('part' => 'Pour les lasagnes', 'liste' => array (
            array('nom' => 'Farine', 'quantite' => '1 kg'),
            array('nom' => 'Oeufs', 'quantite' => '5'),
            array('nom' => 'Huile', 'quantite' => '1 cuillère à soupe'),
            array ('nom' => 'Sel'),
        )),
    ),
   'preparation' => array(
    array (
      'step' => 'Préparation des lasagnes',
      'description' => 'Dans un grand saladier, mélanger la farine, les œufs et y ajouter une cuillère à soupe d’huile.
Bien pétrir la pâte avec de l’eau tiède salée jusqu’à l’obtention d’une boule non collante.
La pâte est prête, couvrir le saladier avec un torchon propre et laisser reposer 1 heure.',
    ),
    array (
      'step' => 'Préparation de la viande en sauce',
      'description' => 'Couper la viande en morceaux et faire d’abord revenir le bœuf et le veau puis le poulet dans une grande marmite. Attendre quelques minutes et mettre la viande dans un plat.
Couper l’oignon et les gousses d’ail en petits morceaux et les faire revenir dans la marmite sans les brûler. Remettre la viande dans la marmite.
Dans un bol, verser tout le coulis et remplir le bol de vin rouge. Verser le mélange dans la marmite, laisser évaporer le vin pendant 2 minutes et couvrir la viande d’eau.
Saler, poivrer, ajouter du thym et laisser mijoter à petit feu pendant au moins 1 heure.',
    ),
    array (
      'step' => 'Séchage et cuisson des lasagnes',
      'description' => 'Étaler la boule en une pâte fine et la laisser sécher 1 heure sur une grande table.
Avant de faire cuire, couper la pâte en carrés de taille moyenne (6 cm de coté environ).
Dans une marmite remplie à ¾ d’eau, mettre du sel et une cuillère à soupe d’huile.
Faire bouillir à feu fort, ajouter les lasagnes et laisser cuire pendant 15 minutes.',
    ),
    array (
      'step' => 'Présentation du plat',
      'description' => 'Dans un grand saladier mettre dans l’ordre : une couche de sauce sans viande, une couche de parmesan fort mélangé avec du poivre et une couche de lasagnes.
Refaire la même opération jusqu’à ce que le saladier soit plein avec une dernière couche de parmesan/ poivre.
La viande est présentée dans un plat avec sa sauce.

Servir chaud.',
    ),
  ),
);

$form->bind($lasagnes);


return $form;