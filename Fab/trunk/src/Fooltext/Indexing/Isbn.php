<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Indexing
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Indexing;

/**
 * Indexe un ISBN. Gère les isbn à 10 et 13 chiffres
 * - un isb13 est indexé tel quel :
 *   "978-2-1234-5680-3" -> keywords='9872123456803'
 * - un isbn10 et indexé comme isbn10 ET comme isbn13 :
 * 	"2-1234-5680-2" -> keywords='2123456802', '9872123456803'
 *
 */
class Isbn implements AnalyzerInterface
{
    const BAD='__bad';

    public function analyze(AnalyzerData $data)
    {
        foreach ($data->content as $value)
        {
            $isbn = preg_replace('~[^0-9xX]~', '', $value);
            switch (strlen($isbn))
            {
                // Cas d'un (vieil) ISBN 10 chifres
                case 10:
                    // Isbn 10 invalide, on indexe à "bad"
                    if (! $this->isValidIsbn10($isbn))
                    {
                        echo "ISBN 10 invalide : $isbn\n";
                        $isbn = self::BAD;
                    }

                    // Isbn 10 valide, on index l'isbn 10 et sa version isbn 13
                    else
                    {
                        // Convertit l'isbn 10 en isbn 13
                        $isbn13 = $this->isbn13($isbn);
                        echo "ISBN 10 valide : $isbn = $isbn13\n";
                        $isbn = array($isbn, $isbn13);
                    }

                    break;

                case 13:
                    // Isbn 13 invalide, on indexe à "bad"
                    if (! $this->isValidIsbn13($isbn))
                    {
                        echo "ISBN 13 invalide : $isbn\n";
                        $isbn = self::BAD;
                    }

                    // Isbn 13 valide, on l'indexe
                    else
                    {
                        echo "ISBN 13 valide : $isbn\n";
                    }
                    break;

                default:
                    echo "autre chose qu'un ISBN : $isbn, len=", strlen($isbn), "\n";
                    $isbn = self::BAD;
            }
            $data->keywords[] = $isbn;
        }
    }

    /**
     * Construit la version 13 chiffres d'un ISBN à 10 chiffres
     *
     * @param string $isbn un isbn à 10 chiffres
     * @return string|false l'isbn à 13 chiffres correspondant ou false si l'issn
     * passé en paramètre contenait autre chose que 10 chiffres.
     */
    protected function isbn13($isbn)
    {
        // Enlève le dernier caractère (caractère de contrôle de l'isbn 10)
        $isbn = substr($isbn, 0, -1);

        // Il doit rester 9 chiffres. Si ce n'est aps le cas, terminé, ce n'est pas un isbn10 correct.
        if (strlen($isbn) !== 9 || ! ctype_digit($isbn)) return false;

        // Additionne les chiffres avec leurs poids respectifs
        $checksum = 38                                                   // Checksum de "978" (9+3*7+8)
            + 3 * ($isbn{0} + $isbn{2} + $isbn{4} + $isbn{6} + $isbn{8}) // Poids de 3
            + $isbn{1} + $isbn{3} + $isbn{5} + $isbn{7};                 // Poids de 1

        // Détermine le caractère de contrôle à partir de la checksum
        $char = (10 - ($checksum % 10)) % 10;

        // Construit l'isbn 13 final
        return '978' . $isbn . $char;
    }

    protected function isValidIsbn13($isbn)
    {
        $check = 0;
        for ($i = 0; $i < 13; $i+=2) $check += substr($isbn, $i, 1);
        for ($i = 1; $i < 12; $i+=2) $check += 3 * substr($isbn, $i, 1);
        return $check % 10 == 0;
    }

    protected function isValidIsbn10($isbn)
    {
        $check = 0;
        for ($i = 0; $i < 9; $i++) $check += (10 - $i) * substr($isbn, $i, 1);
        $t = substr($isbn, 9, 1); // tenth digit (aka checksum or check digit)
        $check += ($t == 'x' || $t == 'X') ? 10 : $t;
        return $check % 11 == 0;
    }
}