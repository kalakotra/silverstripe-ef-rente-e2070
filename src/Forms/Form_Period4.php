<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 4 (page 2 copy 1, printed row 4). Only reached when Period3More = yes.
 */
class Form_Period4 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 4;

    private static $default_sort_order = 7;

    private static array $conditional_required = [
        'Period4Activity',
        'Period4Employer',
        'Period4Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period5::class);
    }
}
