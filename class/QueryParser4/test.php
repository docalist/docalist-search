<?php
/**
 * This file is part of the "Docalist Search" plugin.
*
* Copyright (C) 2011-2016 Daniel Ménard
*
* For copyright and license information, please view the
* LICENSE.txt file that was distributed with this source code.
*
* @package     Docalist
* @subpackage  Search
* @author      Daniel Ménard <daniel.menard@laposte.net>
*/
namespace Docalist\Search\QueryParser4;

include 'Runtime.php';
include 'EvalRuntime.php';
include 'DebugRuntime.php';
include 'MultiRuntime.php';
include 'QueryBuilder.php';
include 'PrattParser.php';
include 'QueryParser.php';

xdebug_break();
$parser = new QueryParser();

$string = " 5  +2 \n\t";
$string = ' 1 + 2*3 - 8/4';
//$string = " 2 + -1 ";
//$string = ' 1 + 2*3 - 8/4 title:12 "hello world""bonjour monde"';
$string = 'title:a OR b c NOT d';

$string='bon    *bon bo*n bon*      *bo*n *bon*   bo*n*';
$string='bon    ?bon bo?n bon?      ?bo?n ?bon?   bo?n?';
$string='bon    *?bon bo*?n bon*?      *?bo*?n *?bon*?   bo*?n*?';
//$string='bon    *?*?';
$string='300..500€';
$string='[300    TO 500]';
$string=' a OR b AND c AND d NOT e ';
$string=' a title:b';
//$string=' NOT b OR c';
$string='+a b';
$string=' le programme affiche "hello world", pas "bonjour le monde". ';
$string='title:"hello world!" OR bonjour published author:"Daniel Ménard" OR dmenard';
$string='(title:"hello world!" OR bonjour)) published author:"Daniel Ménard" OR dmenard';
//$string='title:("hello world!" OR bonjour) published author:"Daniel Ménard" dmenard';

echo "<h1>Chaine analysée : <code>", var_export($string), "</code></h1>";

// $tokens = $parser->tokenize($string);
// var_dump($tokens);

$result = $parser->parse($string);
//var_dump($result);
//echo "<pre>", $result, "</pre>";
echo "<pre>", var_export($result, true), "</pre>";

die();