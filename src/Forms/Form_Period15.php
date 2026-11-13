<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 15 (page 2 copy 2, printed row 7). Only reached when Period14More = yes.
 */
class Form_Period15 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 15;

    private static $default_sort_order = 18;

    private static array $conditional_required = [
        'Period15Activity',
        'Period15Employer',
        'Period15Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period16::class);
    }
}
