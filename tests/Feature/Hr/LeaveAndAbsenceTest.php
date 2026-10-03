<?php

namespace Tests\Feature\Hr;

use App\Models\LeaveBalanceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\Concerns\CreatesEmployees;
use Tests\TestCase;

class LeaveAndAbsenceTest extends TestCase
{
    use CreatesEmployees;
    use RefreshDatabase;

    public function test_employee_can_request_leave_and_hr_approval_notifies_both_sides(): void
    {
        $hr = $this->hrUser();
        $employee = $this->employee(['email' => 'salarie@paie.test']);
        $user = $this->userFor($employee);

        $this->actingAs($user)->post(route('leaves.store'), [
            'leave_type' => 'annual',
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-06',
            'reason' => 'Famille',
        ])->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'employee_id' => $employee->id,
            'status' => 'pending',
            'days_count' => 3,
        ]);
        $this->assertSame('Nouvelle demande de congé', $hr->notifications()->first()->data['title']);
        $this->assertTrue($this->mailContains('Nouvelle demande de congé'));

        $leaveId = $employee->leaves()->first()->id;

        $this->actingAs($user)
            ->post(route('leaves.approve', $leaveId))
            ->assertForbidden();

        $this->actingAs($hr)
            ->post(route('leaves.approve', $leaveId))
            ->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'id' => $leaveId,
            'status' => 'approved',
            'validator_id' => $hr->employee_id,
        ]);
        $this->assertSame(1, LeaveBalanceHistory::query()->where('leave_id', $leaveId)->count());
        $this->assertSame('Demande de congé : approuvée', $user->notifications()->first()->data['title']);
        $this->assertTrue($this->mailContains('Demande de congé : approuvée'));
    }

    public function test_hr_can_reject_an_absence_request(): void
    {
        $hr = $this->hrUser();
        $employee = $this->employee(['email' => 'absent@paie.test']);
        $user = $this->userFor($employee);

        $this->actingAs($user)->post(route('absences.store'), [
            'type' => 'medical',
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-12',
            'reason' => 'Consultation',
        ])->assertRedirect(route('absences.index'));

        $absenceId = $employee->absences()->first()->id;
        $this->assertSame('Nouvelle demande d\'absence', $hr->notifications()->first()->data['title']);

        $this->actingAs($hr)
            ->post(route('absences.reject', $absenceId))
            ->assertRedirect();

        $this->assertDatabaseHas('absences', [
            'id' => $absenceId,
            'status' => 'rejected',
        ]);
        $this->assertSame('Demande d\'absence : rejetée', $user->notifications()->first()->data['title']);
        $this->assertTrue($this->mailContains('Demande d\'absence : rejetée'));
    }

    private function mailContains(string $subject): bool
    {
        $transport = Mail::getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        return $transport->messages()->contains(function ($sent) use ($subject): bool {
            $message = $sent->getOriginalMessage();

            return $message instanceof Email && $message->getSubject() === $subject;
        });
    }
}
