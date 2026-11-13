<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 14 (page 2 copy 2, printed row 6). Only reached when Period13More = yes.
 */
class Form_Period14 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 14;

    private static $default_sort_order = 17;

    private static array $conditional_required = [
        'Period14Activity',
        'Period14Employer',
        'Period14Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period15::class);
    }
}
