<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 3 (page 2 copy 1, printed row 3). Only reached when Period2More = yes.
 */
class Form_Period3 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 3;

    private static $default_sort_order = 6;

    private static array $conditional_required = [
        'Period3Activity',
        'Period3Employer',
        'Period3Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period4::class);
    }
}
