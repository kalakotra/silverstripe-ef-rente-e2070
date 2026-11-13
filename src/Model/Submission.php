<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Model;

use app\forms\basemodel\BaseSubmission;
use mikehaertl\pdftk\Pdf;
use RuntimeException;

/**
 * E 207 submission (DRV E2070-00 - Angaben über den Beschäftigungsverlauf des Versicherten).
 *
 * Mapping conventions (see BaseSubmission::getArrayDataForPDF):
 *   'PDF field' => 'DbField'                       plain value
 *   'PDF field' => 'Method:DbField:Arg'            computed value (always exactly 3 parts)
 *
 * $field_mapping covers page 1 and the rows 1-8 of page 2 (periods 1-8). Periods 9-16 are printed on a second copy
 * of page 2: periodPageData() re-uses the row mapping with the period numbers shifted by 8. The printed row numbers
 * of that copy are masked and reprinted as 9-16 (Hinweise: "fortlaufende Nummerierung").
 * All text fields use fixed font sizes (page 1: 10 pt, table: 9 pt / 8 pt): the step fields carry max lengths,
 * the multiline table cells are word-wrapped here (pdftk cuts words wider than the cell).
 */
class Submission extends BaseSubmission
{
    private static string $table_name = 'Kalakotra_EFormRenteE2070_Submission';

    /** Periods per copy of page 2 (rows 1-8). */
    public const ROWS_PER_PAGE = 8;

    /** Number of Form_PeriodN steps. */
    public const MAX_PERIODS = 16;

    /** Period types that ask for the activity and the place of work. */
    private const TYPES_WITH_ACTIVITY = ['employee', 'self_employed', 'civil_servant', 'training', 'military', 'other'];

    /** Period types that ask for the employer / school / kind of self-employment. */
    private const TYPES_WITH_EMPLOYER = ['employee', 'self_employed', 'civil_servant', 'training'];

    /** Column 3 "Art der Zeiten" (Anmerkung 18): short labels, the cell is only 60 pt wide. */
    private const TYPE_LABELS = [
        'employee' => 'Arbeitnehmer',
        'self_employed' => 'Selbständiger',
        'civil_servant' => 'Beamter',
        'training' => 'Ausbildung',
        'military' => 'Wehrdienst',
        'unemployed' => 'arbeitslos',
        'sickness' => 'Krankheit',
        'household' => 'Haushalt',
        'residence' => 'Wohnzeit',
        'other' => 'Sonstiges',
    ];

    /** Multiline table cells of page 2: usable width (pt), font size (pt), lines. */
    private const CELLS = [
        'art' => [59.0, 9.0, 4],
        'sitz' => [131.5, 9.0, 4],
        'ort' => [110.5, 9.0, 4],
        'wohnort' => [109.5, 9.0, 4],
    ];


    private static array $db = [
        // Ziffer 1
        'LastName' => 'Varchar(45)',
        'BirthNameDiffers' => 'Varchar(10)',
        'BirthName' => 'Varchar(45)',
        'FirstNames' => 'Varchar(45)',
        'FormerNames' => 'Varchar(45)',
        'Gender' => 'Varchar(10)',
        'FatherName' => 'Varchar(40)',
        'MotherName' => 'Varchar(40)',
        // Ziffer 2 - 4
        'Nationality' => 'Varchar(24)',
        'NaturalizationDate' => 'Date',
        'ForeignIdNumber' => 'Varchar(25)',
        'BirthDate' => 'Date',
        'BirthPlace' => 'Varchar(60)',
        'BirthRegion' => 'Varchar(45)',
        'BirthCountry' => 'Varchar(40)',
        'Street' => 'Varchar(70)',
        'PostalCode' => 'Varchar(10)',
        'City' => 'Varchar(55)',
        'Country' => 'Varchar(30)',
        'Phone' => 'Varchar(25)',
        // Ziffer 5
        'InsuranceNumber' => 'Varchar(20)',
        'Kennzeichen' => 'Varchar(4)',
        'ForeignInsuranceNumber' => 'Varchar(22)',
        // Ziffer 7 - period 1
        'Period1From' => 'Date',
        'Period1To' => 'Date',
        'Period1Type' => 'Varchar(20)',
        'Period1Activity' => 'Varchar(30)',
        'Period1Employer' => 'Varchar(90)',
        'Period1Workplace' => 'Varchar(70)',
        'Period1Insurer' => 'Varchar(30)',
        'Period1InsuranceNumber' => 'Varchar(30)',
        'Period1InsuranceType' => 'Varchar(20)',
        'Period1Residence' => 'Varchar(70)',
        'Period1More' => 'Varchar(10)',
        // Ziffer 7 - period 2
        'Period2From' => 'Date',
        'Period2To' => 'Date',
        'Period2Type' => 'Varchar(20)',
        'Period2Activity' => 'Varchar(30)',
        'Period2Employer' => 'Varchar(90)',
        'Period2Workplace' => 'Varchar(70)',
        'Period2Insurer' => 'Varchar(30)',
        'Period2InsuranceNumber' => 'Varchar(30)',
        'Period2InsuranceType' => 'Varchar(20)',
        'Period2Residence' => 'Varchar(70)',
        'Period2More' => 'Varchar(10)',
        // Ziffer 7 - period 3
        'Period3From' => 'Date',
        'Period3To' => 'Date',
        'Period3Type' => 'Varchar(20)',
        'Period3Activity' => 'Varchar(30)',
        'Period3Employer' => 'Varchar(90)',
        'Period3Workplace' => 'Varchar(70)',
        'Period3Insurer' => 'Varchar(30)',
        'Period3InsuranceNumber' => 'Varchar(30)',
        'Period3InsuranceType' => 'Varchar(20)',
        'Period3Residence' => 'Varchar(70)',
        'Period3More' => 'Varchar(10)',
        // Ziffer 7 - period 4
        'Period4From' => 'Date',
        'Period4To' => 'Date',
        'Period4Type' => 'Varchar(20)',
        'Period4Activity' => 'Varchar(30)',
        'Period4Employer' => 'Varchar(90)',
        'Period4Workplace' => 'Varchar(70)',
        'Period4Insurer' => 'Varchar(30)',
        'Period4InsuranceNumber' => 'Varchar(30)',
        'Period4InsuranceType' => 'Varchar(20)',
        'Period4Residence' => 'Varchar(70)',
        'Period4More' => 'Varchar(10)',
        // Ziffer 7 - period 5
        'Period5From' => 'Date',
        'Period5To' => 'Date',
        'Period5Type' => 'Varchar(20)',
        'Period5Activity' => 'Varchar(30)',
        'Period5Employer' => 'Varchar(90)',
        'Period5Workplace' => 'Varchar(70)',
        'Period5Insurer' => 'Varchar(30)',
        'Period5InsuranceNumber' => 'Varchar(30)',
        'Period5InsuranceType' => 'Varchar(20)',
        'Period5Residence' => 'Varchar(70)',
        'Period5More' => 'Varchar(10)',
        // Ziffer 7 - period 6
        'Period6From' => 'Date',
        'Period6To' => 'Date',
        'Period6Type' => 'Varchar(20)',
        'Period6Activity' => 'Varchar(30)',
        'Period6Employer' => 'Varchar(90)',
        'Period6Workplace' => 'Varchar(70)',
        'Period6Insurer' => 'Varchar(30)',
        'Period6InsuranceNumber' => 'Varchar(30)',
        'Period6InsuranceType' => 'Varchar(20)',
        'Period6Residence' => 'Varchar(70)',
        'Period6More' => 'Varchar(10)',
        // Ziffer 7 - period 7
        'Period7From' => 'Date',
        'Period7To' => 'Date',
        'Period7Type' => 'Varchar(20)',
        'Period7Activity' => 'Varchar(30)',
        'Period7Employer' => 'Varchar(90)',
        'Period7Workplace' => 'Varchar(70)',
        'Period7Insurer' => 'Varchar(30)',
        'Period7InsuranceNumber' => 'Varchar(30)',
        'Period7InsuranceType' => 'Varchar(20)',
        'Period7Residence' => 'Varchar(70)',
        'Period7More' => 'Varchar(10)',
        // Ziffer 7 - period 8
        'Period8From' => 'Date',
        'Period8To' => 'Date',
        'Period8Type' => 'Varchar(20)',
        'Period8Activity' => 'Varchar(30)',
        'Period8Employer' => 'Varchar(90)',
        'Period8Workplace' => 'Varchar(70)',
        'Period8Insurer' => 'Varchar(30)',
        'Period8InsuranceNumber' => 'Varchar(30)',
        'Period8InsuranceType' => 'Varchar(20)',
        'Period8Residence' => 'Varchar(70)',
        'Period8More' => 'Varchar(10)',
        // Ziffer 7 - period 9
        'Period9From' => 'Date',
        'Period9To' => 'Date',
        'Period9Type' => 'Varchar(20)',
        'Period9Activity' => 'Varchar(30)',
        'Period9Employer' => 'Varchar(90)',
        'Period9Workplace' => 'Varchar(70)',
        'Period9Insurer' => 'Varchar(30)',
        'Period9InsuranceNumber' => 'Varchar(30)',
        'Period9InsuranceType' => 'Varchar(20)',
        'Period9Residence' => 'Varchar(70)',
        'Period9More' => 'Varchar(10)',
        // Ziffer 7 - period 10
        'Period10From' => 'Date',
        'Period10To' => 'Date',
        'Period10Type' => 'Varchar(20)',
        'Period10Activity' => 'Varchar(30)',
        'Period10Employer' => 'Varchar(90)',
        'Period10Workplace' => 'Varchar(70)',
        'Period10Insurer' => 'Varchar(30)',
        'Period10InsuranceNumber' => 'Varchar(30)',
        'Period10InsuranceType' => 'Varchar(20)',
        'Period10Residence' => 'Varchar(70)',
        'Period10More' => 'Varchar(10)',
        // Ziffer 7 - period 11
        'Period11From' => 'Date',
        'Period11To' => 'Date',
        'Period11Type' => 'Varchar(20)',
        'Period11Activity' => 'Varchar(30)',
        'Period11Employer' => 'Varchar(90)',
        'Period11Workplace' => 'Varchar(70)',
        'Period11Insurer' => 'Varchar(30)',
        'Period11InsuranceNumber' => 'Varchar(30)',
        'Period11InsuranceType' => 'Varchar(20)',
        'Period11Residence' => 'Varchar(70)',
        'Period11More' => 'Varchar(10)',
        // Ziffer 7 - period 12
        'Period12From' => 'Date',
        'Period12To' => 'Date',
        'Period12Type' => 'Varchar(20)',
        'Period12Activity' => 'Varchar(30)',
        'Period12Employer' => 'Varchar(90)',
        'Period12Workplace' => 'Varchar(70)',
        'Period12Insurer' => 'Varchar(30)',
        'Period12InsuranceNumber' => 'Varchar(30)',
        'Period12InsuranceType' => 'Varchar(20)',
        'Period12Residence' => 'Varchar(70)',
        'Period12More' => 'Varchar(10)',
        // Ziffer 7 - period 13
        'Period13From' => 'Date',
        'Period13To' => 'Date',
        'Period13Type' => 'Varchar(20)',
        'Period13Activity' => 'Varchar(30)',
        'Period13Employer' => 'Varchar(90)',
        'Period13Workplace' => 'Varchar(70)',
        'Period13Insurer' => 'Varchar(30)',
        'Period13InsuranceNumber' => 'Varchar(30)',
        'Period13InsuranceType' => 'Varchar(20)',
        'Period13Residence' => 'Varchar(70)',
        'Period13More' => 'Varchar(10)',
        // Ziffer 7 - period 14
        'Period14From' => 'Date',
        'Period14To' => 'Date',
        'Period14Type' => 'Varchar(20)',
        'Period14Activity' => 'Varchar(30)',
        'Period14Employer' => 'Varchar(90)',
        'Period14Workplace' => 'Varchar(70)',
        'Period14Insurer' => 'Varchar(30)',
        'Period14InsuranceNumber' => 'Varchar(30)',
        'Period14InsuranceType' => 'Varchar(20)',
        'Period14Residence' => 'Varchar(70)',
        'Period14More' => 'Varchar(10)',
        // Ziffer 7 - period 15
        'Period15From' => 'Date',
        'Period15To' => 'Date',
        'Period15Type' => 'Varchar(20)',
        'Period15Activity' => 'Varchar(30)',
        'Period15Employer' => 'Varchar(90)',
        'Period15Workplace' => 'Varchar(70)',
        'Period15Insurer' => 'Varchar(30)',
        'Period15InsuranceNumber' => 'Varchar(30)',
        'Period15InsuranceType' => 'Varchar(20)',
        'Period15Residence' => 'Varchar(70)',
        'Period15More' => 'Varchar(10)',
        // Ziffer 7 - period 16
        'Period16From' => 'Date',
        'Period16To' => 'Date',
        'Period16Type' => 'Varchar(20)',
        'Period16Activity' => 'Varchar(30)',
        'Period16Employer' => 'Varchar(90)',
        'Period16Workplace' => 'Varchar(70)',
        'Period16Insurer' => 'Varchar(30)',
        'Period16InsuranceNumber' => 'Varchar(30)',
        'Period16InsuranceType' => 'Varchar(20)',
        'Period16Residence' => 'Varchar(70)',
    ];

    private static array $field_mapping = [
        // Ziffer 1
        'PAF_Vers_Name' => 'LastName',
        'PAF_Vers_GebName' => 'BirthNameOrIdem:BirthName:BirthNameDiffers',
        'PAF_Vers_Vorname' => 'FirstNames',
        'X_PAF_Vers_FrNamen' => 'FormerNames',
        'PAF_Vers_Geschlecht' => 'PdfValue:Gender:gender',
        'VERS_N_VN_VATER_1' => 'FatherName',
        'VERS_GN_VN_MUTTER_1' => 'MotherName',
        // 1.8 Sozialversicherungsnummer and 5.1 share this field name (two widgets)
        'PAF_VSNR' => 'InsuranceNumber',
        // Ziffer 2
        'PAF_Vers_Staatsangehörigkeit' => 'NationalityLine:Nationality:NaturalizationDate',
        'VERS_KENN_NR_1' => 'ForeignIdNumber',
        // Ziffer 3
        'PAF_Vers_GebDat' => 'DateFormating:BirthDate:1',
        'PAF_Vers_GebOrt' => 'BirthPlace',
        'VERS_GBEZIRK_1' => 'BirthRegion',
        'PAF_Vers_GebLand' => 'BirthCountry',
        // Ziffer 4 (three address lines)
        'PAF_Vers_Straße_Postfach' => 'Street',
        'PAF_Vers_Ort' => 'PostalCity:PostalCode:City',
        'PAF_Vers_Land' => 'CountryPhone:Country:Phone',
        // Ziffer 5
        'PAF_AIGR' => 'Compact:Kennzeichen:4',
        'VERS_VSNR_2' => 'ForeignInsuranceNumber',
        // Ziffer 7.2 Datum (page 2, signed by the applicant - Anmerkung 23); the source field is irrelevant
        'VERS_DATUM_02' => 'SubmissionDate:Signature:1',
        // Ziffer 7, periods 1-8 (periods 9-16: periodPageData())
        // row 1
        'VERS_VON_01' => 'DateFormating:Period1From:1',
        'VERS_BIS_01' => 'PeriodTo:Period1To:Period1From',
        'VERS_ART_01' => 'PeriodType:Period1Type:Period1Activity',
        'VERS_SITZ_01' => 'Cell:Period1Employer:sitz',
        'VERS_ORT_01' => 'Cell:Period1Workplace:ort',
        'VERS_V_TR_01' => 'Period1Insurer',
        'VERS_V_NR_01' => 'Period1InsuranceNumber',
        'VERS_V_ART_01' => 'PdfValue:Period1InsuranceType:insurance_type',
        'VERS_W_ORT_01' => 'Cell:Period1Residence:wohnort',
        // row 2
        'VERS_VON_02' => 'DateFormating:Period2From:1',
        'VERS_BIS_02' => 'PeriodTo:Period2To:Period2From',
        'VERS_ART_02' => 'PeriodType:Period2Type:Period2Activity',
        'VERS_SITZ_02' => 'Cell:Period2Employer:sitz',
        'VERS_ORT_02' => 'Cell:Period2Workplace:ort',
        'VERS_V_TR_02' => 'Period2Insurer',
        'VERS_V_NR_02' => 'Period2InsuranceNumber',
        'VERS_V_ART_02' => 'PdfValue:Period2InsuranceType:insurance_type',
        'VERS_W_ORT_02' => 'Cell:Period2Residence:wohnort',
        // row 3
        'VERS_VON_03' => 'DateFormating:Period3From:1',
        'VERS_BIS_03' => 'PeriodTo:Period3To:Period3From',
        'VERS_ART_03' => 'PeriodType:Period3Type:Period3Activity',
        'VERS_SITZ_03' => 'Cell:Period3Employer:sitz',
        'VERS_ORT_03' => 'Cell:Period3Workplace:ort',
        'VERS_V_TR_03' => 'Period3Insurer',
        'VERS_V_NR_03' => 'Period3InsuranceNumber',
        'VERS_V_ART_03' => 'PdfValue:Period3InsuranceType:insurance_type',
        'VERS_W_ORT_03' => 'Cell:Period3Residence:wohnort',
        // row 4
        'VERS_VON_04' => 'DateFormating:Period4From:1',
        'VERS_BIS_04' => 'PeriodTo:Period4To:Period4From',
        'VERS_ART_04' => 'PeriodType:Period4Type:Period4Activity',
        'VERS_SITZ_04' => 'Cell:Period4Employer:sitz',
        'VERS_ORT_04' => 'Cell:Period4Workplace:ort',
        'VERS_V_TR_04' => 'Period4Insurer',
        'VERS_V_NR_04' => 'Period4InsuranceNumber',
        'VERS_V_ART_04' => 'PdfValue:Period4InsuranceType:insurance_type',
        'VERS_W_ORT_04' => 'Cell:Period4Residence:wohnort',
        // row 5
        'VERS_VON_05' => 'DateFormating:Period5From:1',
        'VERS_BIS_05' => 'PeriodTo:Period5To:Period5From',
        'VERS_ART_05' => 'PeriodType:Period5Type:Period5Activity',
        'VERS_SITZ_05' => 'Cell:Period5Employer:sitz',
        'VERS_ORT_05' => 'Cell:Period5Workplace:ort',
        'VERS_V_TR_05' => 'Period5Insurer',
        'VERS_V_NR_05' => 'Period5InsuranceNumber',
        'VERS_V_ART_05' => 'PdfValue:Period5InsuranceType:insurance_type',
        'VERS_W_ORT_05' => 'Cell:Period5Residence:wohnort',
        // row 6
        'VERS_VON_06' => 'DateFormating:Period6From:1',
        'VERS_BIS_06' => 'PeriodTo:Period6To:Period6From',
        'VERS_ART_06' => 'PeriodType:Period6Type:Period6Activity',
        'VERS_SITZ_06' => 'Cell:Period6Employer:sitz',
        'VERS_ORT_06' => 'Cell:Period6Workplace:ort',
        'VERS_V_TR_06' => 'Period6Insurer',
        'VERS_V_NR_06' => 'Period6InsuranceNumber',
        'VERS_V_ART_06' => 'PdfValue:Period6InsuranceType:insurance_type',
        'VERS_W_ORT_06' => 'Cell:Period6Residence:wohnort',
        // row 7
        'VERS_VON_07' => 'DateFormating:Period7From:1',
        'VERS_BIS_07' => 'PeriodTo:Period7To:Period7From',
        'VERS_ART_07' => 'PeriodType:Period7Type:Period7Activity',
        'VERS_SITZ_07' => 'Cell:Period7Employer:sitz',
        'VERS_ORT_07' => 'Cell:Period7Workplace:ort',
        'VERS_V_TR_07' => 'Period7Insurer',
        'VERS_V_NR_07' => 'Period7InsuranceNumber',
        'VERS_V_ART_07' => 'PdfValue:Period7InsuranceType:insurance_type',
        'VERS_W_ORT_07' => 'Cell:Period7Residence:wohnort',
        // row 8
        'VERS_VON_08' => 'DateFormating:Period8From:1',
        'VERS_BIS_08' => 'PeriodTo:Period8To:Period8From',
        'VERS_ART_08' => 'PeriodType:Period8Type:Period8Activity',
        'VERS_SITZ_08' => 'Cell:Period8Employer:sitz',
        'VERS_ORT_08' => 'Cell:Period8Workplace:ort',
        'VERS_V_TR_08' => 'Period8Insurer',
        'VERS_V_NR_08' => 'Period8InsuranceNumber',
        'VERS_V_ART_08' => 'PdfValue:Period8InsuranceType:insurance_type',
        'VERS_W_ORT_08' => 'Cell:Period8Residence:wohnort',
    ];

    /** Values printed differently than stored: $pdf_values[set][value] (PdfValue:Field:set). */
    private static array $pdf_values = [
        'gender' => [
            'male' => 'M',
            'female' => 'F',
        ],
        'insurance_type' => [
            'mandatory' => 'Pflichtversicherung',
            'voluntary' => 'freiwillige Versicherung',
            'continued' => 'freiwillige Weiterversicherung',
            'none' => 'versicherungsloser Zeitraum',
        ],
    ];

    /**
     * Declarative dependency rules (see FieldConditions). Single source for step cleanup, conditional
     * required validation and the onBeforeWrite safety net. Mirrors the display-logic in the steps.
     */
    private static array $field_conditions = [
        [['BirthName'], ['BirthNameDiffers' => 'yes']],
        // period 2 only after "weiterer Zeitraum" = ja in period 1 (whole-step gate, parents first)
        [
            [
                'Period2From', 'Period2To', 'Period2Type', 'Period2Activity',
                'Period2Employer', 'Period2Workplace', 'Period2Insurer', 'Period2InsuranceNumber',
                'Period2InsuranceType', 'Period2Residence', 'Period2More',
            ],
            ['Period1More' => 'yes'],
        ],
        // period 3 only after "weiterer Zeitraum" = ja in period 2 (whole-step gate, parents first)
        [
            [
                'Period3From', 'Period3To', 'Period3Type', 'Period3Activity',
                'Period3Employer', 'Period3Workplace', 'Period3Insurer', 'Period3InsuranceNumber',
                'Period3InsuranceType', 'Period3Residence', 'Period3More',
            ],
            ['Period2More' => 'yes'],
        ],
        // period 4 only after "weiterer Zeitraum" = ja in period 3 (whole-step gate, parents first)
        [
            [
                'Period4From', 'Period4To', 'Period4Type', 'Period4Activity',
                'Period4Employer', 'Period4Workplace', 'Period4Insurer', 'Period4InsuranceNumber',
                'Period4InsuranceType', 'Period4Residence', 'Period4More',
            ],
            ['Period3More' => 'yes'],
        ],
        // period 5 only after "weiterer Zeitraum" = ja in period 4 (whole-step gate, parents first)
        [
            [
                'Period5From', 'Period5To', 'Period5Type', 'Period5Activity',
                'Period5Employer', 'Period5Workplace', 'Period5Insurer', 'Period5InsuranceNumber',
                'Period5InsuranceType', 'Period5Residence', 'Period5More',
            ],
            ['Period4More' => 'yes'],
        ],
        // period 6 only after "weiterer Zeitraum" = ja in period 5 (whole-step gate, parents first)
        [
            [
                'Period6From', 'Period6To', 'Period6Type', 'Period6Activity',
                'Period6Employer', 'Period6Workplace', 'Period6Insurer', 'Period6InsuranceNumber',
                'Period6InsuranceType', 'Period6Residence', 'Period6More',
            ],
            ['Period5More' => 'yes'],
        ],
        // period 7 only after "weiterer Zeitraum" = ja in period 6 (whole-step gate, parents first)
        [
            [
                'Period7From', 'Period7To', 'Period7Type', 'Period7Activity',
                'Period7Employer', 'Period7Workplace', 'Period7Insurer', 'Period7InsuranceNumber',
                'Period7InsuranceType', 'Period7Residence', 'Period7More',
            ],
            ['Period6More' => 'yes'],
        ],
        // period 8 only after "weiterer Zeitraum" = ja in period 7 (whole-step gate, parents first)
        [
            [
                'Period8From', 'Period8To', 'Period8Type', 'Period8Activity',
                'Period8Employer', 'Period8Workplace', 'Period8Insurer', 'Period8InsuranceNumber',
                'Period8InsuranceType', 'Period8Residence', 'Period8More',
            ],
            ['Period7More' => 'yes'],
        ],
        // period 9 only after "weiterer Zeitraum" = ja in period 8 (whole-step gate, parents first)
        [
            [
                'Period9From', 'Period9To', 'Period9Type', 'Period9Activity',
                'Period9Employer', 'Period9Workplace', 'Period9Insurer', 'Period9InsuranceNumber',
                'Period9InsuranceType', 'Period9Residence', 'Period9More',
            ],
            ['Period8More' => 'yes'],
        ],
        // period 10 only after "weiterer Zeitraum" = ja in period 9 (whole-step gate, parents first)
        [
            [
                'Period10From', 'Period10To', 'Period10Type', 'Period10Activity',
                'Period10Employer', 'Period10Workplace', 'Period10Insurer', 'Period10InsuranceNumber',
                'Period10InsuranceType', 'Period10Residence', 'Period10More',
            ],
            ['Period9More' => 'yes'],
        ],
        // period 11 only after "weiterer Zeitraum" = ja in period 10 (whole-step gate, parents first)
        [
            [
                'Period11From', 'Period11To', 'Period11Type', 'Period11Activity',
                'Period11Employer', 'Period11Workplace', 'Period11Insurer', 'Period11InsuranceNumber',
                'Period11InsuranceType', 'Period11Residence', 'Period11More',
            ],
            ['Period10More' => 'yes'],
        ],
        // period 12 only after "weiterer Zeitraum" = ja in period 11 (whole-step gate, parents first)
        [
            [
                'Period12From', 'Period12To', 'Period12Type', 'Period12Activity',
                'Period12Employer', 'Period12Workplace', 'Period12Insurer', 'Period12InsuranceNumber',
                'Period12InsuranceType', 'Period12Residence', 'Period12More',
            ],
            ['Period11More' => 'yes'],
        ],
        // period 13 only after "weiterer Zeitraum" = ja in period 12 (whole-step gate, parents first)
        [
            [
                'Period13From', 'Period13To', 'Period13Type', 'Period13Activity',
                'Period13Employer', 'Period13Workplace', 'Period13Insurer', 'Period13InsuranceNumber',
                'Period13InsuranceType', 'Period13Residence', 'Period13More',
            ],
            ['Period12More' => 'yes'],
        ],
        // period 14 only after "weiterer Zeitraum" = ja in period 13 (whole-step gate, parents first)
        [
            [
                'Period14From', 'Period14To', 'Period14Type', 'Period14Activity',
                'Period14Employer', 'Period14Workplace', 'Period14Insurer', 'Period14InsuranceNumber',
                'Period14InsuranceType', 'Period14Residence', 'Period14More',
            ],
            ['Period13More' => 'yes'],
        ],
        // period 15 only after "weiterer Zeitraum" = ja in period 14 (whole-step gate, parents first)
        [
            [
                'Period15From', 'Period15To', 'Period15Type', 'Period15Activity',
                'Period15Employer', 'Period15Workplace', 'Period15Insurer', 'Period15InsuranceNumber',
                'Period15InsuranceType', 'Period15Residence', 'Period15More',
            ],
            ['Period14More' => 'yes'],
        ],
        // period 16 only after "weiterer Zeitraum" = ja in period 15 (whole-step gate, parents first)
        [
            [
                'Period16From', 'Period16To', 'Period16Type', 'Period16Activity',
                'Period16Employer', 'Period16Workplace', 'Period16Insurer', 'Period16InsuranceNumber',
                'Period16InsuranceType', 'Period16Residence',
            ],
            ['Period15More' => 'yes'],
        ],
        [['Period1Activity', 'Period1Workplace'], ['Period1Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period1Employer'], ['Period1Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period2Activity', 'Period2Workplace'], ['Period2Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period2Employer'], ['Period2Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period3Activity', 'Period3Workplace'], ['Period3Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period3Employer'], ['Period3Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period4Activity', 'Period4Workplace'], ['Period4Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period4Employer'], ['Period4Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period5Activity', 'Period5Workplace'], ['Period5Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period5Employer'], ['Period5Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period6Activity', 'Period6Workplace'], ['Period6Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period6Employer'], ['Period6Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period7Activity', 'Period7Workplace'], ['Period7Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period7Employer'], ['Period7Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period8Activity', 'Period8Workplace'], ['Period8Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period8Employer'], ['Period8Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period9Activity', 'Period9Workplace'], ['Period9Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period9Employer'], ['Period9Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period10Activity', 'Period10Workplace'], ['Period10Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period10Employer'], ['Period10Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period11Activity', 'Period11Workplace'], ['Period11Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period11Employer'], ['Period11Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period12Activity', 'Period12Workplace'], ['Period12Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period12Employer'], ['Period12Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period13Activity', 'Period13Workplace'], ['Period13Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period13Employer'], ['Period13Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period14Activity', 'Period14Workplace'], ['Period14Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period14Employer'], ['Period14Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period15Activity', 'Period15Workplace'], ['Period15Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period15Employer'], ['Period15Type' => self::TYPES_WITH_EMPLOYER]],
        [['Period16Activity', 'Period16Workplace'], ['Period16Type' => self::TYPES_WITH_ACTIVITY]],
        [['Period16Employer'], ['Period16Type' => self::TYPES_WITH_EMPLOYER]],
    ];

    /**
     * Signature next to "7.3 Unterschrift" on every copy of page 2 (mm from the top-left corner of the landscape page).
     * PDFMultiFormPage.SignaturePositionX/Y override x/y (same layout on every copy).
     */
    private static array $signature_position = [
        'x' => 234.0,
        'y' => 182.0,
        'width' => 28.0,
    ];

    /** White masks over the printed row numbers 1-8 of a page 2 copy + reprinted numbers (mm, landscape page 2). */
    private static array $row_numbers = [
        'mask_x' => 18.3,
        'mask_width' => 3.9,
        'mask_height' => 3.6,
        'text_x' => 20.2,
        'first_baseline' => 44.5,
        'row_pitch' => 16.93,
        'size' => 8,
    ];

    /**
     * TTF used by pdftk for field appearances (ž, ć, đ ... are dropped by pdftk's own appearance generation).
     * Relative to the module root or absolute; '' disables it. Liberation Sans: SIL OFL 1.1, see fonts/.
     */
    private static string $replacement_font = 'fonts/LiberationSans-Regular.ttf';

    /**
     * Safety net: values of inactive (hidden or skipped) fields are never stored, whatever the steps delivered.
     */
    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        $rules = (array) $this->config()->get('field_conditions');
        $data = [];
        foreach (FieldConditions::referencedFields($rules) as $field) {
            $data[$field] = $this->getField($field);
        }

        $cleaned = FieldConditions::apply($data, $rules);
        foreach ($cleaned as $field => $value) {
            if ($value === null && $data[$field] !== null) {
                $this->setField($field, null);
            }
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Mapping helpers (signature fixed by BaseSubmission::getArrayDataForPDF: method($field, $arg))
    // ---------------------------------------------------------------------------------------------

    /** Value printed differently than stored ($pdf_values[$set]). */
    public function PdfValue(string $fieldName, string $set): string
    {
        $map = (array) ($this->config()->get('pdf_values')[$set] ?? []);

        return (string) ($map[(string) ($this->getField($fieldName) ?? '')] ?? '');
    }

    /** 1.2: the Geburtsname is always given - "IDEM" when identical with the name (Anmerkung 3). */
    public function BirthNameOrIdem(string $fieldName, string $differsField): string
    {
        $differs = (string) ($this->getField($differsField) ?? '');
        if ($differs === 'no') {
            return 'IDEM';
        }

        return $differs === 'yes' ? trim((string) ($this->getField($fieldName) ?? '')) : '';
    }

    /** Ziffer 2: nationality + date of naturalisation (Anmerkung 8) in the 182 pt box. */
    public function NationalityLine(string $fieldName, string $dateField): string
    {
        $nationality = trim((string) ($this->getField($fieldName) ?? ''));
        $date = (string) ($this->getField($dateField) ?? '');
        if ($nationality === '' || $date === '' || !strtotime($date)) {
            return $nationality;
        }

        $line = $nationality . ' seit ' . date('d.m.Y', (int) strtotime($date));

        return CellText::fits($line, 182.0, 10.0)
            ? $line
            : $nationality . ' seit ' . date('d.m.y', (int) strtotime($date));
    }

    /** Comb-like fields (Kennzeichen): uppercase, no whitespace, cut to the box count. */
    public function Compact(string $fieldName, string $length): string
    {
        $value = strtoupper((string) preg_replace('/\s+/', '', (string) ($this->getField($fieldName) ?? '')));

        return (int) $length > 0 ? substr($value, 0, (int) $length) : $value;
    }

    /** Ziffer 4, line 2. */
    public function PostalCity(string $postalField, string $cityField): string
    {
        $postal = trim((string) ($this->getField($postalField) ?? ''));
        $city = trim((string) ($this->getField($cityField) ?? ''));

        return trim($postal . ' ' . $city);
    }

    /** Ziffer 4, line 3: country + telephone number (Anmerkung 13). */
    public function CountryPhone(string $countryField, string $phoneField): string
    {
        $country = trim((string) ($this->getField($countryField) ?? ''));
        $phone = trim((string) ($this->getField($phoneField) ?? ''));

        if ($phone === '') {
            return $country;
        }

        return ltrim($country . ', Tel. ' . $phone, ', ');
    }

    /** 7.2 Datum: date of the submission. */
    public function SubmissionDate(string $unused = '', string $unused2 = ''): string
    {
        $timestamp = $this->Created ? strtotime((string) $this->Created) : time();

        return date('d.m.Y', $timestamp ?: time());
    }

    /** Column 2 "Bis": empty while the period is still running ("laufend"). */
    public function PeriodTo(string $fieldName, string $fromField): string
    {
        $to = (string) ($this->getField($fieldName) ?? '');
        if ($to !== '' && strtotime($to)) {
            return date('d.m.Y', (int) strtotime($to));
        }

        return (string) ($this->getField($fromField) ?? '') !== '' ? 'laufend' : '';
    }

    /** Column 3 "Art der Zeiten": short type label + activity (e.g. "Arbeitnehmer / Schlosser"), wrapped. */
    public function PeriodType(string $typeField, string $activityField): string
    {
        $label = self::TYPE_LABELS[(string) ($this->getField($typeField) ?? '')] ?? '';
        if ($label === '') {
            return '';
        }

        $activity = trim((string) ($this->getField($activityField) ?? ''));
        [$width, $size, $lines] = self::CELLS['art'];

        return CellText::wrap($activity === '' ? $label : $label . "\n" . $activity, $width, $size, $lines);
    }

    /** Multiline table cell, word-wrapped to the cell width ($cell = key of CELLS). */
    public function Cell(string $fieldName, string $cell): string
    {
        $value = trim((string) ($this->getField($fieldName) ?? ''));
        if ($value === '' || !isset(self::CELLS[$cell])) {
            return '';
        }

        [$width, $size, $lines] = self::CELLS[$cell];

        return CellText::wrap($value, $width, $size, $lines);
    }

    /**
     * Whether $text fits the multiline table cell $cell (without dropping lines). Used by the period steps:
     * the max lengths cover normal text, but CAPITALS or long words need more room.
     */
    public static function cellFits(string $cell, string $text): bool
    {
        if (!isset(self::CELLS[$cell]) || trim($text) === '') {
            return true;
        }

        [$width, $size, $lines] = self::CELLS[$cell];

        return substr_count(CellText::wrap($text, $width, $size, $lines + 1), "\n") < $lines;
    }

    /** Column 3 content (type label + activity) fits the cell. */
    public static function periodTypeFits(string $type, string $activity): bool
    {
        $label = self::TYPE_LABELS[$type] ?? '';

        return self::cellFits('art', trim($label . "\n" . trim($activity)));
    }

    // ---------------------------------------------------------------------------------------------
    // Periods 9-16 (further copies of page 2)
    // ---------------------------------------------------------------------------------------------

    /** Highest period number with data (periods after a "no" are cleared in onBeforeWrite). */
    public function periodCount(): int
    {
        $count = 0;
        for ($n = 1; $n <= self::MAX_PERIODS; $n++) {
            if ((string) ($this->getField('Period' . $n . 'From') ?? '') !== '') {
                $count = $n;
            }
        }

        return $count;
    }

    /** Number of page 2 copies (at least one: page 2 is never omitted). */
    public function periodPageCount(): int
    {
        return max(1, (int) ceil($this->periodCount() / self::ROWS_PER_PAGE));
    }

    /**
     * Field data of copy $copy (1-based) of page 2: the row mapping of $field_mapping with the period numbers
     * shifted by 8 per copy, plus the date 7.2.
     *
     * @return array<string, string>
     */
    public function periodPageData(int $copy): array
    {
        $offset = ($copy - 1) * self::ROWS_PER_PAGE;
        $data = [];

        foreach ((array) $this->config()->get('field_mapping') as $pdfField => $source) {
            $pdfField = (string) $pdfField;
            if ($pdfField === 'VERS_DATUM_02') {
                $data[$pdfField] = $this->resolveMapping((string) $source);
                continue;
            }
            if (!preg_match('/^VERS_[A-Z_]+_0[1-8]$/', $pdfField)) {
                continue;
            }

            $shifted = (string) preg_replace_callback(
                '/Period(\d+)/',
                static fn (array $m): string => 'Period' . ((int) $m[1] + $offset),
                (string) $source
            );
            $data[$pdfField] = $this->resolveMapping($shifted);
        }

        return $data;
    }

    /** Same resolution as BaseSubmission::getArrayDataForPDF() for one mapping source. */
    private function resolveMapping(string $source): string
    {
        if (str_contains($source, ':')) {
            [$method, $field, $arg] = explode(':', $source);

            return method_exists($this, $method) ? (string) $this->$method($field, $arg) : '';
        }

        return $this->hasField($source) ? (string) ($this->getField($source) ?? '') : '';
    }

    // ---------------------------------------------------------------------------------------------
    // PDF generation
    // ---------------------------------------------------------------------------------------------

    /**
     * Page 1 + page 2 (periods 1-8), further copies of page 2 for periods 9-16 (row numbers 9-16), pages 3-4
     * (Hinweise - "keine Seite darf weggelassen werden"). Every page 2 copy is signed and dated (Anmerkung 23).
     */
    public function generateFilledPDF(): Pdf
    {
        $template = getcwd() . '/assets/' . $this->FormSubmission()->getBasePDFLink('PDFFile');
        $stamp = $this->signatureStamp();
        $main = $this->getArrayDataForPDF();

        $package = new PdfPackage($this->replacementFontPath());
        $package->add($template, $main, '1-2', [$stamp]);

        for ($copy = 2; $copy <= $this->periodPageCount(); $copy++) {
            [$masks, $texts] = $this->rowNumberOverlay($copy);
            $package->add($template, $this->periodPageData($copy), '2', [$stamp], $masks, $texts);
        }

        $package->add($template, $main, '3-4');

        $signature = trim((string) $this->Signature);
        $signaturePng = $signature !== '' ? $this->writeSignatureImage($signature) : null;

        try {
            return new Pdf($package->build($signaturePng));
        } finally {
            if ($signaturePng !== null) {
                @unlink($signaturePng);
            }
        }
    }

    /**
     * BaseSubmission data + ASCII fallback is handled by PdfPackage; this only normalises null to ''.
     *
     * @return array<string, mixed>
     */
    public function getArrayDataForPDF(): array
    {
        return array_map(static fn ($value) => $value ?? '', parent::getArrayDataForPDF());
    }

    /**
     * @return array<string, float|int>
     */
    private function signatureStamp(): array
    {
        $config = (array) $this->config()->get('signature_position');
        $holder = $this->getMyHolderPage();

        return [
            'page' => 2,
            'x' => (float) ($holder?->SignaturePositionX ?: ($config['x'] ?? 234.0)),
            'y' => (float) ($holder?->SignaturePositionY ?: ($config['y'] ?? 182.0)),
            'width' => (float) ($config['width'] ?? 28.0),
        ];
    }

    /**
     * Masks the printed row numbers 1-8 of page 2 and prints the continued numbering (9-16 on copy 2).
     *
     * @return array{0: array<int, array<string, float|int>>, 1: array<int, array<string, mixed>>}
     */
    private function rowNumberOverlay(int $copy): array
    {
        $c = (array) $this->config()->get('row_numbers');
        $masks = [];
        $texts = [];

        for ($row = 1; $row <= self::ROWS_PER_PAGE; $row++) {
            $baseline = (float) $c['first_baseline'] + ($row - 1) * (float) $c['row_pitch'];
            $masks[] = [
                'page' => 2,
                'x' => (float) $c['mask_x'],
                'y' => $baseline - (float) $c['mask_height'] + 0.6,
                'width' => (float) $c['mask_width'],
                'height' => (float) $c['mask_height'],
            ];
            $texts[] = [
                'page' => 2,
                'x' => (float) $c['text_x'],
                'y' => $baseline,
                'text' => (string) (($copy - 1) * self::ROWS_PER_PAGE + $row),
                'size' => (float) $c['size'],
            ];
        }

        return [$masks, $texts];
    }

    /** Config replacement_font: absolute path or relative to the module root; empty disables it. */
    private function replacementFontPath(): ?string
    {
        $font = trim((string) $this->config()->get('replacement_font'));
        if ($font === '') {
            return null;
        }

        $path = str_starts_with($font, '/') ? $font : dirname(__DIR__, 2) . '/' . $font;

        return is_file($path) ? $path : null;
    }

    /**
     * jSignature delivers a PNG data URI; base64 SVG is supported as fallback (needs Imagick).
     */
    private function writeSignatureImage(string $signature): string
    {
        $path = sys_get_temp_dir() . '/e2070_sig_' . uniqid('', true) . '.png';

        if (preg_match('#^data:image/png;base64,(.+)$#i', $signature, $matches)) {
            $png = base64_decode($matches[1], true);
            if ($png === false || file_put_contents($path, $png) === false) {
                throw new RuntimeException('Invalid PNG signature payload.');
            }

            return $path;
        }

        $svgBase64 = preg_match('#^data:image/svg\+xml(?:;charset=[^;]+)?;base64,(.+)$#i', $signature, $matches)
            ? $matches[1]
            : $signature;
        $svg = base64_decode($svgBase64, true);
        if ($svg === false) {
            throw new RuntimeException('Invalid signature payload.');
        }

        $imagick = new \Imagick();
        $imagick->readImageBlob($svg);
        $imagick->setBackgroundColor(new \ImagickPixel('white'));
        $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        $imagick->setImageFormat('png');
        $imagick->writeImage($path);
        $imagick->clear();

        return $path;
    }
}
