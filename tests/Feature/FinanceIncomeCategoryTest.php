<?php

namespace Tests\Feature;

use App\Models\IncomeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceIncomeCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_category_can_be_added_from_the_income_page_and_used_for_an_income_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('finance.income'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Income/Index')
                ->has('categories', 0));

        $this->from(route('finance.income'))
            ->post(route('finance.categories.store'), [
                'type' => 'income',
                'name' => 'Consulting Revenue',
                'code' => 'CONSULT',
                'description' => 'Client consulting services',
            ])
            ->assertRedirect(route('finance.income'))
            ->assertSessionHas('success', 'Category created successfully.');

        $category = IncomeCategory::where('code', 'CONSULT')->firstOrFail();

        $this->get(route('finance.income'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Income/Index')
                ->has('categories', 1)
                ->where('categories.0.id', $category->id)
                ->where('categories.0.name', 'Consulting Revenue'));

        $this->from(route('finance.income'))
            ->post(route('finance.income.store'), [
                'title' => 'Consulting payment',
                'category_id' => $category->id,
                'amount' => 1500,
                'date' => now()->toDateString(),
                'payment_method' => 'bank_transfer',
                'payer' => 'Client Corp',
            ])
            ->assertRedirect(route('finance.income'))
            ->assertSessionHas('success', 'Income transaction recorded successfully.');

        $this->assertDatabaseHas('incomes', [
            'title' => 'Consulting payment',
            'category_id' => $category->id,
            'created_by' => $admin->id,
        ]);
    }

    public function test_income_category_code_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        IncomeCategory::create([
            'name' => 'Existing category',
            'code' => 'CONSULT',
        ]);

        $this->actingAs($admin)
            ->from(route('finance.income'))
            ->post(route('finance.categories.store'), [
                'type' => 'income',
                'name' => 'Another category',
                'code' => 'CONSULT',
            ])
            ->assertRedirect(route('finance.income'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('income_categories', 1);
    }
}
