<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 8 (page 2 copy 1, printed row 8). Only reached when Period7More = yes.
 */
class Form_Period8 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 8;

    private static $default_sort_order = 11;

    private static array $conditional_required = [
        'Period8Activity',
        'Period8Employer',
        'Period8Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period9::class);
    }
}
