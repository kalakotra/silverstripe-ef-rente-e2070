<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 13 (page 2 copy 2, printed row 5). Only reached when Period12More = yes.
 */
class Form_Period13 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 13;

    private static $default_sort_order = 16;

    private static array $conditional_required = [
        'Period13Activity',
        'Period13Employer',
        'Period13Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period14::class);
    }
}
