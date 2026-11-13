<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 16 = row 8 of the second copy of page 2. Only reached when Period15More = yes; last period.
 */
class Form_Period16 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 16;

    private static $default_sort_order = 19;

    private static array $conditional_required = [
        'Period16Activity',
        'Period16Employer',
        'Period16Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(null);
    }
}
