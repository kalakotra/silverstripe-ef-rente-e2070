<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 2 - 4 - Staatsangehörigkeit, Geburt, Anschrift.
 */
class Form_Step2 extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 2;

    private static $next_steps = Form_Step3::class;

    public function getFields(): FieldList
    {
        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Staatsangehörigkeit, Geburt und Anschrift'), 4),
            TextField::create('Nationality', _t(__CLASS__ . '.Nationality', 'Staatsangehörigkeit'))
                ->setMaxLength(24)
                ->setDescription(_t(__CLASS__ . '.NationalityDescription', 'z. B. deutsch, kroatisch, bosnisch')),
            DateField::create('NaturalizationDate', _t(__CLASS__ . '.NaturalizationDate', 'Datum der Einbürgerung (optional)')),
            TextField::create('ForeignIdNumber', _t(__CLASS__ . '.ForeignIdNumber', 'Kenn-Nummer (optional)'))
                ->setMaxLength(25)
                ->setDescription(_t(
                    __CLASS__ . '.ForeignIdNumberDescription',
                    'Je nach ausländischem Träger, z. B. PESEL (Polen), EMŠO (Slowenien), Geburtsnummer (Tschechien, Slowakei), AHV-Nummer (Schweiz)'
                )),
            HeaderField::create('BirthHeader', _t(__CLASS__ . '.BirthHeader', 'Geburt'), 5),
            DateField::create('BirthDate', _t(__CLASS__ . '.BirthDate', 'Geburtsdatum')),
            TextField::create('BirthPlace', _t(__CLASS__ . '.BirthPlace', 'Geburtsort'))
                ->setMaxLength(60),
            TextField::create('BirthRegion', _t(__CLASS__ . '.BirthRegion', 'Provinz, Departement, Bezirk (optional)'))
                ->setMaxLength(45)
                ->setDescription(_t(__CLASS__ . '.BirthRegionDescription', 'Zwingend bei spanischen, französischen und italienischen Versicherten')),
            TextField::create('BirthCountry', _t(__CLASS__ . '.BirthCountry', 'Geburtsland'))
                ->setMaxLength(40)
                ->setDescription(_t(__CLASS__ . '.BirthCountryDescription', 'Möglichst als Ländercode (ISO 3166-1), z. B. DE, HR, BA, TR')),
            HeaderField::create('AddressHeader', _t(__CLASS__ . '.AddressHeader', 'Anschrift'), 5),
            TextField::create('Street', _t(__CLASS__ . '.Street', 'Straße und Hausnummer / Postfach'))
                ->setMaxLength(70),
            TextField::create('PostalCode', _t(__CLASS__ . '.PostalCode', 'Postleitzahl'))
                ->setMaxLength(10),
            TextField::create('City', _t(__CLASS__ . '.City', 'Ort'))
                ->setMaxLength(55),
            TextField::create('Country', _t(__CLASS__ . '.Country', 'Land'))
                ->setMaxLength(30),
            TextField::create('Phone', _t(__CLASS__ . '.Phone', 'Telefonnummer (optional)'))
                ->setMaxLength(25),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'Nationality',
            'BirthDate',
            'BirthPlace',
            'BirthCountry',
            'Street',
            'PostalCode',
            'City',
            'Country',
        ]);
    }
}
