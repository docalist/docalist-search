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

namespace Docalist\Search\Mapping\Field\Factory;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Field\BinaryField;
use Docalist\Search\Mapping\Field\BooleanField;
use Docalist\Search\Mapping\Field\ByteField;
use Docalist\Search\Mapping\Field\CompletionField;
use Docalist\Search\Mapping\Field\DateField;
use Docalist\Search\Mapping\Field\DoubleField;
use Docalist\Search\Mapping\Field\FloatField;
use Docalist\Search\Mapping\Field\GeopointField;
use Docalist\Search\Mapping\Field\GeoshapeField;
use Docalist\Search\Mapping\Field\HalfFloatField;
use Docalist\Search\Mapping\Field\IntegerField;
use Docalist\Search\Mapping\Field\IPField;
use Docalist\Search\Mapping\Field\KeywordField;
use Docalist\Search\Mapping\Field\LongField;
use Docalist\Search\Mapping\Field\ObjectField;
use Docalist\Search\Mapping\Field\NestedField;
use Docalist\Search\Mapping\Field\ShortField;
use Docalist\Search\Mapping\Field\TextField;

/**
 * Ce trait contient des méthodes qui permettent de créer tous les types de champs de mapping qui existent.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait BaseFactoryTrait
{
    /**
     * Pour utiliser ce trait, la classe doit avoir une méthode addField().
     *
     * @param Field $field Champ à ajouter.
     *
     * @return Field Le champ ajouté.
     */
    abstract protected function addField(Field $field): Field;

    /**
     * Ajoute un champ de type 'binary'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/binary.html
     *
     * @param string $name Nom du champ
     *
     * @return BinaryField
     */
    public function binary(string $name): BinaryField
    {
        return $this->addField(new BinaryField($name));
    }

    /**
     * Ajoute un champ de type 'boolean'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/boolean.html
     *
     * @param string $name Nom du champ
     *
     * @return Boolean
     */
    public function boolean(string $name): BooleanField
    {
        return $this->addField(new BooleanField($name));
    }

    /**
     * Ajoute un champ de type 'byte'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string $name Nom du champ
     *
     * @return ByteField
     */
    public function byte(string $name): ByteField
    {
        return $this->addField(new ByteField($name));
    }

    /**
     * Ajoute un champ de type 'completion'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters-completion.html
     *
     * @param string $name Nom du champ
     *
     * @return CompletionField
     */
    public function completion(string $name): CompletionField
    {
        return $this->addField(new CompletionField($name));
    }

    /**
     * Ajoute un champ de type 'date'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/date.html
     *
     * @param string $name Nom du champ
     *
     * @return DateField
     */
    public function date(string $name): DateField
    {
        return $this->addField(new DateField($name));
    }

    /**
     * Ajoute un champ de type 'double'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string $name Nom du champ
     *
     * @return DoubleField
     */
    public function double(string $name): DoubleField
    {
        return $this->addField(new DoubleField($name));
    }

    /**
     * Ajoute un champ de type 'float'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string $name Nom du champ
     *
     * @return FloatField
     */
    public function float(string $name): FloatField
    {
        return $this->addField(new FloatField($name));
    }

    /**
     * Ajoute un champ de type 'geo point'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/geo-point.html
     *
     * @param string $name Nom du champ
     *
     * @return GeopointField
     */
    public function geopoint(string $name): GeopointField
    {
        return $this->addField(new GeopointField($name));
    }

    /**
     * Ajoute un champ de type 'geo shape'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/geo-shape.html
     *
     * @param string $name Nom du champ
     *
     * @return GeoshapeField
     */
    public function geoshape(string $name): GeoshapeField
    {
        return $this->addField(new GeoshapeField($name));
    }

    /**
     * Ajoute un champ de type 'half_float'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string $name Nom du champ
     *
     * @return HalfFloatField
     */
    public function halfFloat(string $name): HalfFloatField
    {
        return $this->addField(new HalfFloatField($name));
    }

    /**
     * Ajoute un champ de type 'integer'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string $name Nom du champ
     *
     * @return IntegerField
     */
    public function integer(string $name): IntegerField
    {
        return $this->addField(new IntegerField($name));
    }

    /**
     * Ajoute un champ de type 'ip'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/ip.html
     *
     * @param string $name Nom du champ
     *
     * @return IPField
     */
    public function ip(string $name): IPField
    {
        return $this->addField(new IPField($name));
    }

    /**
     * Ajoute un champ de type 'keyword'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/keyword.html
     *
     * @param string $name Nom du champ
     *
     * @return KeywordField
     */
    public function keyword(string $name): KeywordField
    {
        return $this->addField(new KeywordField($name));
    }

    /**
     * Ajoute un champ de type 'long'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string $name Nom du champ
     *
     * @return LongField
     */
    public function long(string $name): LongField
    {
        return $this->addField(new LongField($name));
    }

    /**
     * Ajoute un champ est de type 'nested'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/nested.html
     *
     * @param string $name Nom du champ
     *
     * @return NestedField
     */
    public function nested(string $name): NestedField
    {
        return $this->addField(new NestedField($name));
    }

    /**
     * Ajoute un champ est de type 'objet'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/object.html
     *
     * @param string $name Nom du champ
     *
     * @return ObjectField
     */
    public function object(string $name): ObjectField
    {
        return $this->addField(new ObjectField($name));
    }

    /**
     * Ajoute un champ de type 'short'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string $name Nom du champ
     *
     * @return ShortField
     */
    public function short(string $name): ShortField
    {
        return $this->addField(new ShortField($name));
    }

    /**
     * Ajoute un champ de type 'text' paramétré avec l'analyseur par défaut.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/text.html
     *
     * @param string $name Nom du champ
     *
     * @return TextField
     */
    public function text(string $name): TextField
    {
        return $this->addField(new TextField($name));
    }
}
