<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Ziffer 1 - Angaben über den Versicherten (Namen, Geschlecht, Eltern).
 * All PDF fields use a fixed 10 pt font: max lengths match the printed boxes.
 */
class Form_Step1 extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 1;

    private static $next_steps = Form_Step2::class;

    private static array $conditional_required = [
        'BirthName',
    ];

    public function getFields(): FieldList
    {
        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Angaben über den Versicherten'), 4),
            LiteralField::create('IntroNotice', '<p>' . _t(
                __CLASS__ . '.IntroNotice',
                'Mit dem Vordruck E 207 (Angaben über den Beschäftigungsverlauf des Versicherten) teilen Sie dem '
                . 'Rentenversicherungsträger alle Zeiten mit, die Sie in Deutschland und in anderen Staaten der EU, '
                . 'des EWR oder in der Schweiz zurückgelegt haben. Ziffer 6 füllt der bearbeitende Träger aus.'
            ) . '</p>'),
            TextField::create('LastName', _t(__CLASS__ . '.LastName', 'Name'))
                ->setMaxLength(45)
                ->setDescription(_t(__CLASS__ . '.LastNameDescription', 'Üblicher Name oder der bei der Eheschließung angenommene Name')),
            DropdownField::create('BirthNameDiffers', _t(__CLASS__ . '.BirthNameDiffers', 'Weicht Ihr Geburtsname von Ihrem jetzigen Namen ab?'))
                ->setSource([
                    'no' => _t(__CLASS__ . '.BirthNameDiffers.no', 'nein (im Vordruck wird „IDEM“ eingetragen)'),
                    'yes' => _t(__CLASS__ . '.BirthNameDiffers.yes', 'ja'),
                ])
                ->setEmptyString('-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --'),
            Wrapper::create(
                TextField::create('BirthName', _t(__CLASS__ . '.BirthName', 'Geburtsname'))
                    ->setMaxLength(45),
            )
                ->displayIf('BirthNameDiffers')->isEqualTo('yes')->end(),
            TextField::create('FirstNames', _t(__CLASS__ . '.FirstNames', 'Vornamen'))
                ->setMaxLength(45)
                ->setDescription(_t(__CLASS__ . '.FirstNamesDescription', 'Alle Vornamen in der Reihenfolge der standesamtlichen Eintragung')),
            TextField::create('FormerNames', _t(__CLASS__ . '.FormerNames', 'Frühere Namen (optional)'))
                ->setMaxLength(45)
                ->setDescription(_t(__CLASS__ . '.FormerNamesDescription', 'Insbesondere bei Adoption oder gebräuchlich gewordenen Beinamen')),
            DropdownField::create('Gender', _t(__CLASS__ . '.Gender', 'Geschlecht'))
                ->setSource([
                    'male' => _t(__CLASS__ . '.Gender.male', 'männlich (M)'),
                    'female' => _t(__CLASS__ . '.Gender.female', 'weiblich (F)'),
                ])
                ->setEmptyString('-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --'),
            HeaderField::create('ParentsHeader', _t(__CLASS__ . '.ParentsHeader', 'Eltern'), 5),
            LiteralField::create('ParentsNotice', '<p>' . _t(
                __CLASS__ . '.ParentsNotice',
                'Diese Angaben werden benötigt, wenn Sie spanische Staatsangehörige / spanischer Staatsangehöriger sind '
                . 'oder der Vordruck für einen französischen Träger bestimmt ist.'
            ) . '</p>'),
            TextField::create('FatherName', _t(__CLASS__ . '.FatherName', 'Name und Vornamen des Vaters (optional)'))
                ->setMaxLength(40),
            TextField::create('MotherName', _t(__CLASS__ . '.MotherName', 'Geburtsname und Vornamen der Mutter (optional)'))
                ->setMaxLength(40),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'LastName',
            'BirthNameDiffers',
            'FirstNames',
            'Gender',
        ]);
    }
}
