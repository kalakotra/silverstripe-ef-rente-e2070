<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 9 (page 2 copy 2, printed row 1). Only reached when Period8More = yes.
 */
class Form_Period9 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 9;

    private static $default_sort_order = 12;

    private static array $conditional_required = [
        'Period9Activity',
        'Period9Employer',
        'Period9Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period10::class);
    }
}
