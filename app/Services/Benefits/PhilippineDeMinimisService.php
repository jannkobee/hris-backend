<?php

namespace App\Services\Benefits;

class PhilippineDeMinimisService
{
    /**
     * Standard Philippine De Minimis Benefit Categories and Tax-Exempt Ceilings.
     * Based on BIR RR No. 2-98 as amended by RR No. 5-2011, RR No. 1-2015, and RR No. 11-2018 (TRAIN Law).
     */
    public const CEILINGS = [
        'rice_subsidy' => [
            'name' => 'Rice Subsidy',
            'category' => 'Meals & Sustenance',
            'ceiling_amount' => 2000.00,
            'frequency' => 'monthly',
            'annual_ceiling' => 24000.00,
            'description' => 'Rice subsidy of ₱2,000 or one (1) 50-kg sack of rice per month.',
            'statutory_reference' => 'RR No. 11-2018 (TRAIN Law)',
        ],
        'uniform_clothing' => [
            'name' => 'Uniform & Clothing Allowance',
            'category' => 'Clothing',
            'ceiling_amount' => 6000.00,
            'frequency' => 'annually',
            'annual_ceiling' => 6000.00,
            'description' => 'Uniform and clothing allowance not exceeding ₱6,000 per annum.',
            'statutory_reference' => 'RR No. 11-2018 (TRAIN Law)',
        ],
        'medical_cash_allowance' => [
            'name' => 'Medical Cash Allowance for Dependents',
            'category' => 'Medical & Healthcare',
            'ceiling_amount' => 250.00,
            'frequency' => 'monthly',
            'annual_ceiling' => 3000.00,
            'description' => 'Medical cash allowance to dependents not exceeding ₱1,500 per semester (₱250/month).',
            'statutory_reference' => 'RR No. 2-98 as amended',
        ],
        'actual_medical_assistance' => [
            'name' => 'Actual Medical Assistance',
            'category' => 'Medical & Healthcare',
            'ceiling_amount' => 10000.00,
            'frequency' => 'annually',
            'annual_ceiling' => 10000.00,
            'description' => 'Actual medical assistance (check-ups, maternity, consultation) not exceeding ₱10,000 per annum.',
            'statutory_reference' => 'RR No. 2-98 as amended',
        ],
        'laundry_allowance' => [
            'name' => 'Laundry Allowance',
            'category' => 'General Allowance',
            'ceiling_amount' => 300.00,
            'frequency' => 'monthly',
            'annual_ceiling' => 3600.00,
            'description' => 'Laundry allowance not exceeding ₱300 per month.',
            'statutory_reference' => 'RR No. 11-2018 (TRAIN Law)',
        ],
        'achievement_awards' => [
            'name' => 'Employee Achievement Awards',
            'category' => 'Awards & Recognition',
            'ceiling_amount' => 10000.00,
            'frequency' => 'annually',
            'annual_ceiling' => 10000.00,
            'description' => 'Tangible personal property for length of service or safety achievement not exceeding ₱10,000 per annum.',
            'statutory_reference' => 'RR No. 2-98 as amended',
        ],
        'holiday_anniversary_gifts' => [
            'name' => 'Christmas & Anniversary Gifts',
            'category' => 'Holiday & Occasion',
            'ceiling_amount' => 5000.00,
            'frequency' => 'annually',
            'annual_ceiling' => 5000.00,
            'description' => 'Gifts given during Christmas and major company anniversary celebrations not exceeding ₱5,000 per annum.',
            'statutory_reference' => 'RR No. 2-98 as amended',
        ],
        'daily_meal_overtime' => [
            'name' => 'Daily Meal for Overtime / Night Shift',
            'category' => 'Meals & Sustenance',
            'ceiling_amount' => 150.00,
            'frequency' => 'daily',
            'annual_ceiling' => null,
            'description' => 'Daily meal allowance for overtime and night shift not exceeding 25% of basic minimum wage.',
            'statutory_reference' => 'RR No. 2-98 as amended',
        ],
    ];

    /**
     * Get all de minimis benefit categories and their statutory limits.
     */
    public function getCeilings(): array
    {
        return self::CEILINGS;
    }

    /**
     * Evaluate whether an expense or benefit amount exceeds the tax-exempt ceiling.
     */
    public function evaluate(string $categoryKey, float $amount): array
    {
        $category = self::CEILINGS[$categoryKey] ?? null;
        if (! $category) {
            return [
                'is_de_minimis' => false,
                'exceeds_ceiling' => false,
                'taxable_excess' => 0.0,
                'non_taxable_amount' => $amount,
            ];
        }

        $ceiling = (float) $category['ceiling_amount'];
        $exceeds = $amount > $ceiling;

        return [
            'is_de_minimis' => true,
            'ceiling_amount' => $ceiling,
            'frequency' => $category['frequency'],
            'exceeds_ceiling' => $exceeds,
            'non_taxable_amount' => min($amount, $ceiling),
            'taxable_excess' => max(0.0, $amount - $ceiling),
            'note' => $exceeds
                ? "Amount exceeds the statutory ceiling of ₱{$ceiling}. The excess of ₱".number_format($amount - $ceiling, 2).' is subject to taxation.'
                : 'Full amount is within the Philippine statutory de minimis tax-exempt threshold.',
        ];
    }
}

