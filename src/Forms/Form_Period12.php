<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 12 (page 2 copy 2, printed row 4). Only reached when Period11More = yes.
 */
class Form_Period12 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 12;

    private static $default_sort_order = 15;

    private static array $conditional_required = [
        'Period12Activity',
        'Period12Employer',
        'Period12Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period13::class);
    }
}
