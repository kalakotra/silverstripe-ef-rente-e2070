<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use Kalakotra\EFormRenteE2070\Model\FieldConditions;
use Kalakotra\EFormRenteE2070\Model\Submission;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * One row of Ziffer 7 (Angaben über alle zurückgelegten Zeiten). Field names are "Period{N}<Field>",
 * N = the using class's PERIOD constant. Labels use __TRAIT__ keys (one translation for all periods);
 * Form::resolveFieldLabel() takes the preview labels from these field titles.
 *
 * Max lengths match the table cells of page 2 (fixed 9 pt font, 4 lines; a/b/c lines 8 pt, one line).
 * Using classes define: public const PERIOD, $default_sort_order, $conditional_required, getNextStep().
 */
trait PeriodStepTrait
{
    public function getFields(): FieldList
    {
        $n = static::PERIOD;
        $p = 'Period' . $n;
        $select = '-- ' . _t(__TRAIT__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';

        $fields = FieldList::create(
            HeaderField::create('IntroText', _t(__TRAIT__ . '.IntroText', 'Zeitraum {number}', ['number' => $n]), 4),
        );

        if ($n === 1) {
            $fields->push(LiteralField::create('PeriodsNotice', '<div class="alert alert-info">' . _t(
                __TRAIT__ . '.PeriodsNotice',
                'Bitte geben Sie alle zurückgelegten Zeiten in zeitlicher Reihenfolge an: Beschäftigung, selbständige '
                . 'Tätigkeit, Beamtentätigkeit, Schul- und Berufsausbildung, Wehrdienst, Arbeitslosigkeit, Krankheit, '
                . 'Haushaltsführung sowie Wohnzeiten. Fügen Sie beglaubigte Abschriften der Nachweise bei (z. B. Rentenbücher, '
                . 'Gehaltszettel, Beitragsbescheinigungen). Bis zu 16 Zeiträume; ab dem 9. Zeitraum wird Seite 2 des Vordrucks '
                . 'fortlaufend nummeriert wiederholt.'
            ) . '</div>'));
        }

        $fields->push(DateField::create($p . 'From', _t(__TRAIT__ . '.From', 'Von')));
        $fields->push(
            DateField::create($p . 'To', _t(__TRAIT__ . '.To', 'Bis'))
                ->setDescription(_t(__TRAIT__ . '.ToDescription', 'Leer lassen, wenn der Zeitraum noch andauert (im Vordruck: „laufend“)'))
        );
        $fields->push(
            DropdownField::create($p . 'Type', _t(__TRAIT__ . '.Type', 'Art der Zeit'))
                ->setSource([
                    'employee' => _t(__TRAIT__ . '.Type.employee', 'Beschäftigung als Arbeitnehmer/in'),
                    'self_employed' => _t(__TRAIT__ . '.Type.self_employed', 'Selbständige Tätigkeit'),
                    'civil_servant' => _t(__TRAIT__ . '.Type.civil_servant', 'Beamtentätigkeit'),
                    'training' => _t(__TRAIT__ . '.Type.training', 'Schul- oder Berufsausbildung'),
                    'military' => _t(__TRAIT__ . '.Type.military', 'Wehrdienst / Zivildienst'),
                    'unemployed' => _t(__TRAIT__ . '.Type.unemployed', 'Arbeitslosigkeit'),
                    'sickness' => _t(__TRAIT__ . '.Type.sickness', 'Krankheit'),
                    'household' => _t(__TRAIT__ . '.Type.household', 'Haushaltsführung / Kindererziehung'),
                    'residence' => _t(__TRAIT__ . '.Type.residence', 'Wohnzeit ohne Erwerbstätigkeit'),
                    'other' => _t(__TRAIT__ . '.Type.other', 'Sonstige Zeit'),
                ])
                ->setEmptyString($select)
        );
        $fields->push(
            Wrapper::create(
                TextField::create($p . 'Activity', _t(__TRAIT__ . '.Activity', 'Ausgeübte Tätigkeit / Beruf / Art der Ausbildung'))
                    ->setMaxLength(30)
                    ->setDescription(_t(__TRAIT__ . '.ActivityDescription', 'z. B. Schlosser, Verkäuferin, Landwirt, Lehre als Elektriker; beim Wehrdienst: Land')),
            )
                ->displayIf($p . 'Type')->isEqualTo('employee')
                ->orIf($p . 'Type')->isEqualTo('self_employed')
                ->orIf($p . 'Type')->isEqualTo('civil_servant')
                ->orIf($p . 'Type')->isEqualTo('training')
                ->orIf($p . 'Type')->isEqualTo('military')
                ->orIf($p . 'Type')->isEqualTo('other')
                ->end()
        );
        $fields->push(
            Wrapper::create(
                TextareaField::create($p . 'Employer', _t(
                    __TRAIT__ . '.Employer',
                    'Bezeichnung und Sitz des Arbeitgebers / der Ausbildungsstätte oder Art der selbständigen Tätigkeit'
                ))
                    ->setRows(2)
                    ->setMaxLength(90),
            )
                ->displayIf($p . 'Type')->isEqualTo('employee')
                ->orIf($p . 'Type')->isEqualTo('self_employed')
                ->orIf($p . 'Type')->isEqualTo('civil_servant')
                ->orIf($p . 'Type')->isEqualTo('training')
                ->end()
        );
        $fields->push(
            Wrapper::create(
                TextField::create($p . 'Workplace', _t(__TRAIT__ . '.Workplace', 'Ort und Land der Tätigkeit'))
                    ->setMaxLength(70)
                    ->setDescription(_t(__TRAIT__ . '.WorkplaceDescription', 'Für Frankreich bitte auch das Departement angeben')),
            )
                ->displayIf($p . 'Type')->isEqualTo('employee')
                ->orIf($p . 'Type')->isEqualTo('self_employed')
                ->orIf($p . 'Type')->isEqualTo('civil_servant')
                ->orIf($p . 'Type')->isEqualTo('training')
                ->orIf($p . 'Type')->isEqualTo('military')
                ->orIf($p . 'Type')->isEqualTo('other')
                ->end()
        );

        $fields->push(HeaderField::create($p . 'InsuranceHeader', _t(__TRAIT__ . '.InsuranceHeader', 'Versicherung in diesem Zeitraum'), 5));
        $fields->push(
            TextField::create($p . 'Insurer', _t(__TRAIT__ . '.Insurer', 'Versicherungsträger oder -system (optional)'))
                ->setMaxLength(30)
                ->setDescription(_t(__TRAIT__ . '.InsurerDescription', 'z. B. Deutsche Rentenversicherung Bund, HZMO, ZUS'))
        );
        $fields->push(
            TextField::create($p . 'InsuranceNumber', _t(__TRAIT__ . '.InsuranceNumber', 'Versicherungsnummer (optional)'))
                ->setMaxLength(30)
        );
        $fields->push(
            DropdownField::create($p . 'InsuranceType', _t(__TRAIT__ . '.InsuranceType', 'Art der Versicherung'))
                ->setSource([
                    'mandatory' => _t(__TRAIT__ . '.InsuranceType.mandatory', 'Pflichtversicherung'),
                    'voluntary' => _t(__TRAIT__ . '.InsuranceType.voluntary', 'freiwillige Versicherung'),
                    'continued' => _t(__TRAIT__ . '.InsuranceType.continued', 'freiwillige Weiterversicherung'),
                    'none' => _t(__TRAIT__ . '.InsuranceType.none', 'versicherungsloser Zeitraum'),
                ])
                ->setEmptyString($select)
        );
        $fields->push(
            TextField::create($p . 'Residence', _t(__TRAIT__ . '.Residence', 'Wohnort während dieses Zeitraums'))
                ->setMaxLength(70)
                ->setDescription(_t(__TRAIT__ . '.ResidenceDescription', 'Ort und Land'))
        );

        if ($n < Form_Period16::PERIOD) {
            $fields->push(
                DropdownField::create($p . 'More', _t(__TRAIT__ . '.More', 'Möchten Sie einen weiteren Zeitraum angeben?'))
                    ->setSource([
                        'no' => _t(__TRAIT__ . '.More.no', 'nein, weiter zur Vorschau'),
                        'yes' => _t(__TRAIT__ . '.More.yes', 'ja'),
                    ])
                    ->setEmptyString($select)
            );
        }

        return $fields;
    }

    public function getValidator(): RequiredFieldsValidator
    {
        $p = 'Period' . static::PERIOD;
        $required = [
            $p . 'From',
            $p . 'Type',
            $p . 'InsuranceType',
            $p . 'Residence',
        ];

        if (static::PERIOD < Form_Period16::PERIOD) {
            $required[] = $p . 'More';
        }

        return RequiredFieldsValidator::create($required);
    }

    /**
     * Called by StepTrait::validateStep(): "Bis" must not be before "Von", and the texts must fit the table cells
     * of the printed form (fixed 9 pt font, 4 lines - CAPITALS and long words need more room than the max length).
     */
    public function validatePeriod($form): bool
    {
        $p = 'Period' . static::PERIOD;
        $data = FieldConditions::apply($form->getData(), (array) Submission::config()->get('field_conditions'), true);
        $valid = true;

        $from = trim((string) ($data[$p . 'From'] ?? ''));
        $to = trim((string) ($data[$p . 'To'] ?? ''));
        if ($from !== '' && $to !== '' && strtotime($to) < strtotime($from)) {
            $form->sessionFieldError(_t(__TRAIT__ . '.ToBeforeFrom', '„Bis“ darf nicht vor „Von“ liegen.'), $p . 'To');
            $valid = false;
        }

        $tooLong = [];
        if (!Submission::periodTypeFits((string) ($data[$p . 'Type'] ?? ''), (string) ($data[$p . 'Activity'] ?? ''))) {
            $tooLong[] = $p . 'Activity';
        }
        foreach (['Employer' => 'sitz', 'Workplace' => 'ort', 'Residence' => 'wohnort'] as $field => $cell) {
            if (!Submission::cellFits($cell, (string) ($data[$p . $field] ?? ''))) {
                $tooLong[] = $p . $field;
            }
        }
        foreach ($tooLong as $fieldName) {
            $form->sessionFieldError(_t(
                __TRAIT__ . '.TooLong',
                'Diese Angabe passt nicht in das Feld des Vordrucks. Bitte kürzen Sie sie (z. B. Abkürzungen, keine Großbuchstaben).'
            ), $fieldName);
            $valid = false;
        }

        if (!$valid) {
            $form->sessionMessage(_t(__TRAIT__ . '.CheckEntries', 'Bitte prüfen Sie die markierten Angaben.'));
        }

        return $valid;
    }

    /**
     * Next period when "weiterer Zeitraum" = yes, otherwise the preview.
     * Own step data is read directly: the cross-step cache could be stale right after saveData().
     */
    protected function periodNextStep(?string $nextPeriodClass): string
    {
        $more = $this->loadData()['Period' . static::PERIOD . 'More'] ?? null;

        return ($more === 'yes' && $nextPeriodClass !== null) ? $nextPeriodClass : Form_Preview::class;
    }
}
