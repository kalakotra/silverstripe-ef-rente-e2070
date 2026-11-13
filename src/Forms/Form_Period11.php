<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 11 (page 2 copy 2, printed row 3). Only reached when Period10More = yes.
 */
class Form_Period11 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 11;

    private static $default_sort_order = 14;

    private static array $conditional_required = [
        'Period11Activity',
        'Period11Employer',
        'Period11Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period12::class);
    }
}
