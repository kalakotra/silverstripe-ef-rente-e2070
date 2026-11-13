<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 6 (page 2 copy 1, printed row 6). Only reached when Period5More = yes.
 */
class Form_Period6 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 6;

    private static $default_sort_order = 9;

    private static array $conditional_required = [
        'Period6Activity',
        'Period6Employer',
        'Period6Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period7::class);
    }
}
