<?php

declare(strict_types=1);

namespace Kalakotra\EFormRenteE2070\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Ziffer 7, row 5 (page 2 copy 1, printed row 5). Only reached when Period4More = yes.
 */
class Form_Period5 extends MultiFormStep
{
    use StepTrait;
    use PeriodStepTrait;

    public const PERIOD = 5;

    private static $default_sort_order = 8;

    private static array $conditional_required = [
        'Period5Activity',
        'Period5Employer',
        'Period5Workplace',
    ];

    public function getNextStep()
    {
        return $this->periodNextStep(Form_Period6::class);
    }
}
