<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 10 (page 2 copy 2, printed row 2). Only reached when Period9More = yes.
 */
class Form_Period10 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 10;

    private static $default_sort_order = 13;

    private static array $conditional_required = [
        'Period10Activity',
        'Period10Employer',
        'Period10Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period11::class);
    }
}
