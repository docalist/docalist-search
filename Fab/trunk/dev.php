<?php
header('content-type: text/html; charset=utf-8');
require_once(__DIR__ . '/autoload.php');
new XapianQuery();
// test query
use Fooltext\Query\OrQuery;
use Fooltext\Query\AndQuery;
use Fooltext\Query\NotQuery;
use Fooltext\Query\AndMaybeQuery;
use Fooltext\Query\PhraseQuery;
use Fooltext\Query\NearQuery;
use Fooltext\Query\WildcardQuery;

if (false)
{
    $q1 = new OrQuery('a','b');

    var_export($q1->getType());
    die();
    $q2 = new OrQuery('c','d');
    $q = new OrQuery($q1, $q2);
    $q = new AndQuery($q, 'z');
    echo "q1 : $q1<br />q2: $q2<br />result: $q<br /><br />";
//     $q1->dump('q1');
//     $q2->dump('q1');
//     $q->dump('result');

    echo "Après optimisation : ", $q->optimize(), '<br />';

    $q = new AndQuery('a', array('b','c',new AndQuery('d','e')));
    echo "Avant optimisation : ", $q, '<br />';
    echo "Après optimisation : ", $q->optimize(), '<br />';

    die();
}
// ********** test parser
use Fooltext\QueryParser\Lexer;
use Fooltext\QueryParser\Parser;

//$equation = "typdoc=article date:2011 titre:\"l'hôpital dans tous ses états\" motscles=[humour]";
$equation = 'a OR b +c d OR e AND D near E';
//$equation = 'a "b c" "d e"';
//$equation = 'a b titre:+';
$equation = 'a OR b AND c NOT d';
$equation = '+';

echo "<p>Equation analysée :<br /><code>$equation</code></p>";

echo "<p>Tokens reconnus par l'analyseur lexical :<br />";
$lexer = new Lexer();
$lexer->dumpTokens($equation);
echo "</p>";

$parser = new Parser();
$query = $parser->parseQuery($equation);
echo "<p>Equation générée par le query parser :<br /><code>", $query, "</code></p>";
echo "<p>Equation optimisée :<br /><code>", $query->optimize(), "</code></p>";


// $q = $query->toXapian();
// echo $q->get_description(), '<br />';

$qp=new XapianQueryParser();
        $flags=
            XapianQueryParser::FLAG_BOOLEAN |
            XapianQueryParser::FLAG_PHRASE |
            XapianQueryParser::FLAG_LOVEHATE |
            XapianQueryParser::FLAG_WILDCARD |
            XapianQueryParser::FLAG_PURE_NOT | XapianQueryParser::FLAG_BOOLEAN_ANY_CASE;

$q = $qp->parse_query($equation, $flags);
$h = $q->get_description();
$h=preg_replace('~:\(pos=\d+?\)~', '', $h);

echo "<p>Equation analysée par le query parser de xapian :<br /><code>$h</code></p>";

die();

// ********** test lexer
$lexer = new Lexer();
$lexer->dumpTokens('MotsCles="a b c"');
die();


// *****************
$schema = new Fooltext\Schema\Schema();
$schema->stopwords = 'le la les de du des a c en';
$catalog = new Fooltext\Schema\Collection(array('name'=>'catalog', 'documentClass'=>'Notice'));
$catalog
    ->addField(array('name'=>'REF'    , 'analyzer'=>array('Fooltext\Indexing\Integer', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'Type'   , 'analyzer'=>array('Fooltext\Indexing\StandardValuesAnalyzer', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'Titre'  , 'analyzer'=>array('Fooltext\Indexing\StandardTextAnalyzer','Fooltext\Indexing\RemoveStopwords')))
    ->addField(array('name'=>'Aut'    , 'analyzer'=>array('Fooltext\Indexing\StandardValuesAnalyzer', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'ISBN'   , 'analyzer'=>array('Fooltext\Indexing\Isbn', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'Visible', 'analyzer'=>array('Fooltext\Indexing\BooleanExtended', 'Fooltext\Indexing\Attribute')));

$schema->addCollection($catalog);

$db = new Fooltext\Store\XapianStore(array('path'=>'f:/temp/test', 'overwrite'=>true, 'schema'=>$schema));


class Notice extends \Fooltext\Document\Document
{
}

echo "Ajout d'un enreg\n";
for ($ref=123; $ref<=124; $ref++)
{
    $db->catalog->put(array
    (
        'REF'=>$ref,
        'Type'=>array('Article','Document électronique'),
        'Titre'=>'Premier essai <i>(sous-titre en italique)</i>',
        'Aut'=>'Ménard (D.)',
        'ISBN'=>array("978-2-1234-5680-3", "2-1234-5680-2"),
        'Visible'=>true,
    ));
}

for ($ref=123; $ref<=124; $ref++)
{
    echo "Appelle get($ref)\n";
    $doc2 = $db->catalog->get($ref);
    echo $doc2, "\n";
//     var_export((array)$doc2);
//     echo serialize($doc2), "\n";
    echo "\n\n";
}