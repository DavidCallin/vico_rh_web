import { Head, router, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatXof } from '@/lib/hr';

type ContractRow = {
    id: number;
    employee_name: string;
    contract_type: string;
    contract_type_label: string;
    start_date: string;
    end_date: string | null;
    base_salary: string;
    has_document: boolean;
    expired: boolean;
    expiring_soon: boolean;
};

type Option = { id: number; name: string };

const selectClass =
    'border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none';

export default function ContractsIndex({
    contracts,
    contractTypes,
    employees,
    isHr,
    reminderDays,
}: {
    contracts: ContractRow[];
    contractTypes: Record<string, string>;
    employees: Option[];
    isHr: boolean;
    reminderDays: number;
}) {
    const reminders = contracts.filter(
        (contract) => contract.expired || contract.expiring_soon,
    );
    const form = useForm({
        employee_id: employees[0]?.id?.toString() ?? '',
        contract_type: 'cdd',
        start_date: '',
        end_date: '',
        base_salary: '',
    });

    return (
        <>
            <Head title="Contrats" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Contrats"
                    description={`Un rappel est envoyé ${reminderDays} jours avant l'échéance, puis lorsque le contrat est échu.`}
                />

                {reminders.length > 0 && (
                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                        <p className="font-medium">Rappels de contrat</p>
                        <ul className="mt-2 space-y-1">
                            {reminders.map((contract) => (
                                <li key={contract.id}>
                                    {contract.employee_name} ·{' '}
                                    {contract.contract_type_label} ·{' '}
                                    {formatDate(contract.end_date)} ·{' '}
                                    {contract.expired ? 'Échu' : 'Bientôt échu'}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {isHr && (
                    <form
                        className="grid gap-4 rounded-xl border p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/contrats');
                        }}
                    >
                        <div className="grid gap-2">
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
                        <div className="grid gap-2">
                            <Label htmlFor="contract_type">Type</Label>
                            <select
                                id="contract_type"
                                className={selectClass}
                                value={form.data.contract_type}
                                onChange={(event) =>
                                    form.setData(
                                        'contract_type',
                                        event.target.value,
                                    )
                                }
                            >
                                {Object.entries(contractTypes).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
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
                            />
                            <InputError message={form.errors.end_date} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="base_salary">
                                Salaire de base (F CFA)
                            </Label>
                            <Input
                                id="base_salary"
                                type="number"
                                min="0"
                                value={form.data.base_salary}
                                onChange={(event) =>
                                    form.setData(
                                        'base_salary',
                                        event.target.value,
                                    )
                                }
                                required
                            />
                            <InputError message={form.errors.base_salary} />
                        </div>
                        <div className="flex items-end">
                            <Button type="submit" disabled={form.processing}>
                                Enregistrer et générer
                            </Button>
                        </div>
                    </form>
                )}

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Salarié</th>
                                <th className="p-3">Type</th>
                                <th className="p-3">Dates</th>
                                <th className="p-3">Salaire</th>
                                <th className="p-3">Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            {contracts.length === 0 && (
                                <tr>
                                    <td
                                        className="text-muted-foreground p-3"
                                        colSpan={5}
                                    >
                                        Aucun contrat.
                                    </td>
                                </tr>
                            )}
                            {contracts.map((contract) => (
                                <tr key={contract.id} className="border-t">
                                    <td className="p-3">
                                        {contract.employee_name}
                                    </td>
                                    <td className="p-3">
                                        {contract.contract_type_label}
                                    </td>
                                    <td className="p-3">
                                        {formatDate(contract.start_date)} –{' '}
                                        {formatDate(contract.end_date)}
                                    </td>
                                    <td className="p-3">
                                        {formatXof(contract.base_salary)}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex gap-2">
                                            {contract.has_document && (
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <a
                                                        href={`/contrats/${contract.id}/document`}
                                                    >
                                                        Télécharger
                                                    </a>
                                                </Button>
                                            )}
                                            {isHr && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        router.post(
                                                            `/contrats/${contract.id}/generer`,
                                                        )
                                                    }
                                                >
                                                    Générer
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

ContractsIndex.layout = {
    breadcrumbs: [{ title: 'Contrats', href: '/contrats' }],
};
