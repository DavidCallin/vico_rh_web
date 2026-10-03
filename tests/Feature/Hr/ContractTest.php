<?php

namespace Tests\Feature\Hr;

use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesEmployees;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use CreatesEmployees;
    use RefreshDatabase;

    public function test_hr_generates_a_contract_document(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();
        $employee = $this->employee(['email' => 'contrat@paie.test']);

        $this->actingAs($hr)->post(route('contracts.store'), [
            'employee_id' => $employee->id,
            'contract_type' => 'cdd',
            'start_date' => '2026-01-01',
            'end_date' => now()->addDays(10)->toDateString(),
            'base_salary' => 250000,
        ])->assertRedirect(route('contracts.index'));

        $contract = Contract::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($contract);
        $this->assertNotNull($contract->pdf_path);
        Storage::disk('local')->assertExists($contract->pdf_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($contract->pdf_path));

        $this->actingAs($hr)
            ->get(route('contracts.download', $contract))
            ->assertOk();
    }

    public function test_dashboard_shows_and_sends_a_reminder_for_a_contract_near_expiry(): void
    {
        $hr = $this->hrUser();
        $employee = $this->employee(['email' => 'echeance@paie.test']);
        $user = $this->userFor($employee);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'contract_type' => 'cdd',
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'base_salary' => 180000,
        ]);

        $this->actingAs($hr)->get(route('dashboard'))->assertOk();

        $this->assertSame('Contrat bientôt échu', $hr->notifications()->first()->data['title']);
        $this->assertSame('Contrat bientôt échu', $user->notifications()->first()->data['title']);

        $this->actingAs($hr)->get(route('dashboard'))->assertOk();
        $this->assertSame(1, $hr->notifications()->count());

        $this->artisan('contracts:remind')->assertSuccessful();
        $this->assertSame(1, $hr->notifications()->count());
    }

    public function test_dashboard_keeps_a_contract_near_expiry_ahead_of_older_ones(): void
    {
        $hr = $this->hrUser();
        $soon = $this->employee(['first_name' => 'Kodjo', 'last_name' => 'Echeance']);
        Contract::factory()->create([
            'employee_id' => $soon->id,
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        foreach (range(1, 9) as $index) {
            Contract::factory()->create([
                'employee_id' => $this->employee()->id,
                'end_date' => now()->subYears(3)->subDays($index)->toDateString(),
            ]);
        }

        $this->actingAs($hr)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('expiringContracts', function ($contracts) {
                $names = collect($contracts)->pluck('employee_name');

                return $names->contains('Kodjo Echeance')
                    && $names->first() === 'Kodjo Echeance';
            }));
    }
}
