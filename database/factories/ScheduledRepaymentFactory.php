<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Loan;
use App\Models\User;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => $this->faker->randomElement([1000, 2000, 3000]),
            'due_date' => now()->addMonth(),
            'is_paid' => false,
        ];
    }

}
