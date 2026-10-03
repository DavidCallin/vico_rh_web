<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use App\Notifications\HrMessageNotification;
use Illuminate\Support\Facades\Notification;

class HrNotifier
{
    public function notifyValidators(Employee $employee, string $title, string $body, string $url, ?User $except = null): void
    {
        $this->notifyHr($title, $body, $url, $except);

        $employee->loadMissing('position.department.manager.user');
        $manager = $employee->position?->department?->manager?->user;

        if (! $manager instanceof User) {
            return;
        }

        if ($manager->hasRole('rh') || $manager->id === $except?->id) {
            return;
        }

        $this->send($manager, $title, $body, $url);
    }

    public function notifyHr(string $title, string $body, string $url, ?User $except = null): void
    {
        $query = User::role('rh');

        if ($except) {
            $query->where('id', '!=', $except->id);
        }

        foreach ($query->get() as $user) {
            $this->send($user, $title, $body, $url);
        }
    }

    public function notifyEmployee(Employee $employee, string $title, string $body, string $url): void
    {
        $employee->loadMissing('user');
        $user = $employee->user;

        if ($user) {
            $this->send($user, $title, $body, $url);

            if ($employee->email && strcasecmp($employee->email, $user->email) !== 0) {
                $this->sendToAddress($employee->email, $title, $body, $url);
            }

            return;
        }

        $this->sendToAddress($employee->email, $title, $body, $url);
    }

    public function payslipIssued(Payslip $payslip): void
    {
        $payslip->loadMissing('employee.user', 'payPeriod');
        $period = $payslip->payPeriod;
        $employee = $payslip->employee;

        if ($period === null || $employee === null) {
            return;
        }
        $label = sprintf('%02d/%d', $period->month, $period->year);
        $net = number_format((float) $payslip->net_salary, 0, ',', ' ');

        $this->notifyEmployee(
            $employee,
            'Bulletin de paie '.$label,
            'Votre bulletin de '.$label.' est disponible. Net à payer : '.$net.' F CFA.',
            route('payslips.show', $payslip),
        );
    }

    public function leaveRequested(Employee $employee, string $summary, ?User $actor): void
    {
        $this->notifyValidators(
            $employee,
            'Nouvelle demande de congé',
            $employee->full_name.' a déposé une demande de congé : '.$summary,
            route('leaves.index'),
            $actor,
        );
    }

    public function absenceRequested(Employee $employee, string $summary, ?User $actor): void
    {
        $this->notifyValidators(
            $employee,
            'Nouvelle demande d\'absence',
            $employee->full_name.' a déposé une demande d\'absence : '.$summary,
            route('absences.index'),
            $actor,
        );
    }

    public function requestDecided(Employee $employee, string $kind, string $status, string $summary): void
    {
        $decision = $this->decisionVerb($status);
        $label = $kind === 'absence' ? 'demande d\'absence' : 'demande de congé';

        $this->notifyEmployee(
            $employee,
            ucfirst($label).' : '.$decision,
            'Votre '.$label.' ('.$summary.') a été '.$decision.'.',
            $kind === 'absence' ? route('absences.index') : route('leaves.index'),
        );
    }

    public function contractReminder(Employee $employee, string $body, bool $expired): void
    {
        $title = $expired ? 'Contrat échu' : 'Contrat bientôt échu';

        $employee->loadMissing('user');
        $this->notifyEmployee($employee, $title, $body, route('contracts.index'));
        $this->notifyHr($title, $body, route('contracts.index'), $employee->user);
    }

    private function decisionVerb(string $status): string
    {
        return $status === 'approved' ? 'approuvée' : 'rejetée';
    }

    private function send(User $user, string $title, string $body, string $url): void
    {
        $user->notify(new HrMessageNotification($title, $body, $url));
    }

    private function sendToAddress(?string $email, string $title, string $body, string $url): void
    {
        if ($email === null || $email === '') {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new HrMessageNotification($title, $body, $url));
    }
}
