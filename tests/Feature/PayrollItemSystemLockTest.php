<?php

namespace Tests\Feature;

use App\Models\PayrollItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Tests\TestCase;

class PayrollItemSystemLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_its_item_cannot_be_updated_deleted_or_toggled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->withoutMiddleware(Authorize::class);

        $itsItem = PayrollItem::factory()->create([
            'code' => 'ITS',
            'name' => 'Impôt sur Traitement et Salaire',
            'type' => 'deduction',
            'taxStatus' => 'non_taxable',
            'calculation' => 'fixed',
            'amount_or_rate' => 0,
            'defaultValue' => 0,
            'displayOrder' => 999,
            'isActive' => false,
        ]);

        $this->putJson('/api/payroll-items/' . $itsItem->id, [
            'code' => 'ITS',
            'name' => 'ITS modifié',
            'type' => 'deduction',
            'taxStatus' => 'non_taxable',
            'calculation' => 'fixed',
            'defaultValue' => 100,
            'displayOrder' => 1,
            'isActive' => true,
        ])->assertStatus(403);

        $this->patchJson('/api/payroll-items/' . $itsItem->id . '/toggle-active', [
            'isActive' => true,
        ])->assertStatus(403);

        $this->deleteJson('/api/payroll-items/' . $itsItem->id)
            ->assertStatus(403);
    }
}
