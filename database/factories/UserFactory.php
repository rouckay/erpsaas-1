<?php

namespace Database\Factories;

use App\Events\CompanyGenerated;
use App\Models\Company;
use App\Models\Setting\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Wallo\FilamentCompanies\FilamentCompanies;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_company_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(static fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user should have a personal company.
     */
    public function withPersonalCompany(): static
    {
        if (! FilamentCompanies::hasCompanyFeatures()) {
            return $this->state([]);
        }

        $countryCode = $this->faker->countryCode;

        return $this->afterCreating(function (User $user) use ($countryCode) {
            Company::factory()
                ->has(CompanyProfile::factory()->withCountry($countryCode), 'profile')
                ->afterCreating(function (Company $company) use ($user, $countryCode) {
                    CompanyGenerated::dispatch($user, $company, $countryCode);

                    $defaultBankAccount = $company->bankAccounts()->where('enabled', true)->first();
                    $defaultCurrency = $company->currencies()->where('enabled', true)->first();
                    $defaultSalesTax = $company->taxes()->where('type', 'sales')->where('enabled', true)->first();
                    $defaultPurchaseTax = $company->taxes()->where('type', 'purchase')->where('enabled', true)->first();
                    $defaultSalesDiscount = $company->discounts()->where('type', 'sales')->where('enabled', true)->first();
                    $defaultPurchaseDiscount = $company->discounts()->where('type', 'purchase')->where('enabled', true)->first();

                    $company->default()->create([
                        'bank_account_id' => $defaultBankAccount?->id,
                        'currency_code' => $defaultCurrency?->code,
                        'sales_tax_id' => $defaultSalesTax?->id,
                        'purchase_tax_id' => $defaultPurchaseTax?->id,
                        'sales_discount_id' => $defaultSalesDiscount?->id,
                        'purchase_discount_id' => $defaultPurchaseDiscount?->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                })
                ->create([
                    'name' => $user->name . '\'s Company',
                    'user_id' => $user->id,
                    'personal_company' => true,
                ]);
        });
    }
}
