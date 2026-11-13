<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 1 (always present). Branches to period 2 or to the preview.
 */
class Form_Period1 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 1;

    private static $default_sort_order = 4;

    private static array $conditional_required = [
        'Period1Activity',
        'Period1Employer',
        'Period1Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period2::class);
    }
}
