<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 5 - Versicherungsnummern. (Ziffer 6 "Bearbeitender Träger" is filled by the authority.)
 */
class Form_Step3 extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 3;

    private static $next_steps = Form_Period1::class;

    public function getFields(): FieldList
    {
        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Versicherungsnummern'), 4),
            TextField::create('InsuranceNumber', _t(
                __CLASS__ . '.InsuranceNumber',
                'Versicherungsnummer der Deutschen Rentenversicherung (soweit bekannt)'
            ))
                ->setMaxLength(20)
                ->setDescription(_t(
                    __CLASS__ . '.InsuranceNumberDescription',
                    'z. B. 12 150385 M 012 - wird unter Ziffer 1.8 und 5.1 eingetragen'
                )),
            TextField::create('Kennzeichen', _t(__CLASS__ . '.Kennzeichen', 'Kennzeichen (soweit bekannt)'))
                ->setMaxLength(4),
            TextField::create('ForeignInsuranceNumber', _t(
                __CLASS__ . '.ForeignInsuranceNumber',
                'Versicherungsnummer beim ausländischen (beteiligten) Träger (soweit bekannt)'
            ))
                ->setMaxLength(22),
            LiteralField::create('AuthorityNotice', '<p class="alert alert-info">' . _t(
                __CLASS__ . '.AuthorityNotice',
                'Ziffer 6 (bearbeitender Träger, Stempel, Datum, Unterschrift) wird von der Rentenversicherung ausgefüllt.'
            ) . '</p>'),
        );
    }
}
