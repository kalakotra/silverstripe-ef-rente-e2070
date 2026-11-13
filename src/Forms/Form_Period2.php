<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 2 (page 2 copy 1, printed row 2). Only reached when Period1More = yes.
 */
class Form_Period2 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 2;

    private static $default_sort_order = 5;

    private static array $conditional_required = [
        'Period2Activity',
        'Period2Employer',
        'Period2Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period3::class);
    }
}
