<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 7 (page 2 copy 1, printed row 7). Only reached when Period6More = yes.
 */
class Form_Period7 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 7;

    private static $default_sort_order = 10;

    private static array $conditional_required = [
        'Period7Activity',
        'Period7Employer',
        'Period7Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period8::class);
    }
}
