<?php

namespace Database\Factories;

use App\Domain\Marketing\Enums\CampaignEffect;
use App\Domain\Marketing\Enums\CampaignTheme;
use App\Domain\Marketing\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => 'Black Friday '.now()->year,
            'theme' => CampaignTheme::BlackFriday,
            'effect' => CampaignEffect::Sparkles,
            'accent_color' => '#000000',
            'headline' => 'Black Friday',
            'subheadline' => 'Los mejores descuentos del año',
            'show_countdown' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(3),
            'is_active' => true,
            'priority' => 0,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(['starts_at' => now()->addWeek(), 'ends_at' => now()->addWeeks(2)]);
    }

    public function ended(): static
    {
        return $this->state(['starts_at' => now()->subWeeks(2), 'ends_at' => now()->subWeek()]);
    }
}
