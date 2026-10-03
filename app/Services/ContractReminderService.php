<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Support\HrLabels;

class ContractReminderService
{
    public function __construct(private HrNotifier $notifier) {}

    public function dispatchDue(): int
    {
        $days = (int) config('payroll.contract_reminder_days');
        $today = now()->startOfDay();
        $horizon = $today->copy()->addDays($days);

        $contracts = Contract::query()
            ->with('employee')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $horizon->toDateString())
            ->get();

        $sent = 0;

        foreach ($contracts as $contract) {
            $end = $contract->end_date->copy()->startOfDay();
            $expired = $end->lt($today);
            $stage = $expired ? 'expired' : 'upcoming';

            if ($contract->reminder_stage === $stage) {
                continue;
            }

            $employee = $contract->employee;

            if (! $employee instanceof Employee) {
                continue;
            }
            $type = HrLabels::contractType($contract->contract_type);
            $when = $end->format('d/m/Y');
            $body = $expired
                ? 'Le contrat '.$type.' de '.$employee->full_name.' est échu depuis le '.$when.'.'
                : 'Le contrat '.$type.' de '.$employee->full_name.' arrive à échéance le '.$when.'.';

            $this->notifier->contractReminder($employee, $body, $expired);
            $contract->update([
                'reminder_stage' => $stage,
                'last_reminded_at' => now(),
            ]);
            $sent++;
        }

        return $sent;
    }
}
