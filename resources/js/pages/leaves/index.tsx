import { Head, router, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/hr';

type LeaveRow = {
    id: number;
    employee_name: string;
    leave_type_label: string;
    start_date: string;
    end_date: string;
    days_count: string;
    status: string;
    status_label: string;
    reason: string | null;
};

type Option = { id: number; name: string };

const selectClass =
    'border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none';

export default function LeavesIndex({
    leaves,
    leaveTypes,
    employees,
    isHr,
    canCreate,
}: {
    leaves: LeaveRow[];
    leaveTypes: Record<string, string>;
    employees: Option[];
    isHr: boolean;
    canCreate: boolean;
}) {
    const form = useForm({
        leave_type: 'annual',
        start_date: '',
        end_date: '',
        reason: '',
        employee_id: employees[0]?.id?.toString() ?? '',
    });

    return (
        <>
            <Head title="Congés" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Demandes de congé"
                    description="Déposez une demande, puis validez-la ou refusez-la."
                />

                {canCreate ? (
                    <form
                        className="grid gap-4 rounded-xl border p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/conges');
                        }}
                    >
                        {isHr && (
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="employee_id">Salarié</Label>
                                <select
                                    id="employee_id"
                                    className={selectClass}
                                    value={form.data.employee_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'employee_id',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {employees.map((employee) => (
                                        <option
                                            key={employee.id}
                                            value={employee.id}
                                        >
                                            {employee.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.employee_id} />
                            </div>
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor="leave_type">Type</Label>
                            <select
                                id="leave_type"
                                className={selectClass}
                                value={form.data.leave_type}
                                onChange={(event) =>
                                    form.setData(
                                        'leave_type',
                                        event.target.value,
                                    )
                                }
                            >
                                {Object.entries(leaveTypes).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                            <InputError message={form.errors.leave_type} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reason">Motif</Label>
                            <Input
                                id="reason"
                                value={form.data.reason}
                                onChange={(event) =>
                                    form.setData('reason', event.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="start_date">Début</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={form.data.start_date}
                                onChange={(event) =>
                                    form.setData(
                                        'start_date',
                                        event.target.value,
                                    )
                                }
                                required
                            />
                            <InputError message={form.errors.start_date} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end_date">Fin</Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={form.data.end_date}
                                onChange={(event) =>
                                    form.setData('end_date', event.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.end_date} />
                        </div>
                        <div className="md:col-span-2">
                            <Button type="submit" disabled={form.processing}>
                                Envoyer la demande
                            </Button>
                        </div>
                    </form>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        Aucun dossier salarié n'est lié à ce compte.
                    </p>
                )}

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Salarié</th>
                                <th className="p-3">Type</th>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Jours</th>
                                <th className="p-3">Statut</th>
                                {isHr && <th className="p-3">Décision</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.length === 0 && (
                                <tr>
                                    <td
                                        className="text-muted-foreground p-3"
                                        colSpan={isHr ? 6 : 5}
                                    >
                                        Aucune demande.
                                    </td>
                                </tr>
                            )}
                            {leaves.map((leave) => (
                                <tr key={leave.id} className="border-t">
                                    <td className="p-3">
                                        {leave.employee_name}
                                    </td>
                                    <td className="p-3">
                                        {leave.leave_type_label}
                                        {leave.reason ? (
                                            <span className="text-muted-foreground block">
                                                {leave.reason}
                                            </span>
                                        ) : null}
                                    </td>
                                    <td className="p-3">
                                        {formatDate(leave.start_date)} –{' '}
                                        {formatDate(leave.end_date)}
                                    </td>
                                    <td className="p-3">{leave.days_count}</td>
                                    <td className="p-3">
                                        {leave.status_label}
                                    </td>
                                    {isHr && (
                                        <td className="p-3">
                                            {leave.status === 'pending' && (
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                `/conges/${leave.id}/approuver`,
                                                            )
                                                        }
                                                    >
                                                        Approuver
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            router.post(
                                                                `/conges/${leave.id}/rejeter`,
                                                            )
                                                        }
                                                    >
                                                        Rejeter
                                                    </Button>
                                                </div>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

LeavesIndex.layout = {
    breadcrumbs: [{ title: 'Congés', href: '/conges' }],
};
