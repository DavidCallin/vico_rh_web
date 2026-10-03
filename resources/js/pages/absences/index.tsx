import { Head, router, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/hr';

type AbsenceRow = {
    id: number;
    employee_name: string;
    type_label: string;
    start_date: string;
    end_date: string;
    status: string;
    status_label: string;
    reason: string | null;
    has_proof: boolean;
};

type Option = { id: number; name: string };

const selectClass =
    'border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none';

export default function AbsencesIndex({
    absences,
    absenceTypes,
    employees,
    isHr,
    canCreate,
}: {
    absences: AbsenceRow[];
    absenceTypes: Record<string, string>;
    employees: Option[];
    isHr: boolean;
    canCreate: boolean;
}) {
    const form = useForm({
        type: 'medical',
        start_date: '',
        end_date: '',
        reason: '',
        employee_id: employees[0]?.id?.toString() ?? '',
        proof: null as File | null,
    });

    return (
        <>
            <Head title="Absences" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Demandes d'absence"
                    description="Une absence est approuvée ou rejetée par les ressources humaines."
                />

                {canCreate ? (
                    <form
                        className="grid gap-4 rounded-xl border p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/absences', { forceFormData: true });
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
                            <Label htmlFor="type">Type</Label>
                            <select
                                id="type"
                                className={selectClass}
                                value={form.data.type}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                            >
                                {Object.entries(absenceTypes).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                            <InputError message={form.errors.type} />
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
                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="proof">Justificatif</Label>
                            <Input
                                id="proof"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={(event) =>
                                    form.setData(
                                        'proof',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <InputError message={form.errors.proof} />
                        </div>
                        <div>
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
                                <th className="p-3">Statut</th>
                                {isHr && <th className="p-3">Décision</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {absences.length === 0 && (
                                <tr>
                                    <td
                                        className="text-muted-foreground p-3"
                                        colSpan={isHr ? 5 : 4}
                                    >
                                        Aucune demande.
                                    </td>
                                </tr>
                            )}
                            {absences.map((absence) => (
                                <tr key={absence.id} className="border-t">
                                    <td className="p-3">
                                        {absence.employee_name}
                                    </td>
                                    <td className="p-3">
                                        {absence.type_label}
                                        {absence.has_proof && (
                                            <a
                                                className="block underline"
                                                href={`/absences/${absence.id}/justificatif`}
                                            >
                                                Justificatif
                                            </a>
                                        )}
                                    </td>
                                    <td className="p-3">
                                        {formatDate(absence.start_date)} –{' '}
                                        {formatDate(absence.end_date)}
                                    </td>
                                    <td className="p-3">
                                        {absence.status_label}
                                    </td>
                                    {isHr && (
                                        <td className="p-3">
                                            {absence.status === 'pending' && (
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                `/absences/${absence.id}/approuver`,
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
                                                                `/absences/${absence.id}/rejeter`,
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

AbsencesIndex.layout = {
    breadcrumbs: [{ title: 'Absences', href: '/absences' }],
};
