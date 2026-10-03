import { Head, router, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Period = {
    id: number;
    label: string;
    status_label: string;
    payslips_count: number;
    can_run: boolean;
};

const selectClass =
    'border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none';

const months = [
    'Janvier',
    'Février',
    'Mars',
    'Avril',
    'Mai',
    'Juin',
    'Juillet',
    'Août',
    'Septembre',
    'Octobre',
    'Novembre',
    'Décembre',
];

export default function PayPeriodsIndex({ periods }: { periods: Period[] }) {
    const today = new Date();
    const form = useForm({
        month: String(today.getMonth() + 1),
        year: String(today.getFullYear()),
    });

    return (
        <>
            <Head title="Paie" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Périodes de paie"
                    description="Le lancement calcule un bulletin par salarié, avec l'ITS verrouillé, puis l'envoie par e-mail et dans l'espace salarié."
                />

                <form
                    className="grid gap-4 rounded-xl border p-4 md:grid-cols-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/paie');
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="month">Mois</Label>
                        <select
                            id="month"
                            className={selectClass}
                            value={form.data.month}
                            onChange={(event) =>
                                form.setData('month', event.target.value)
                            }
                        >
                            {months.map((label, index) => (
                                <option key={label} value={index + 1}>
                                    {label}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.month} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="year">Année</Label>
                        <Input
                            id="year"
                            type="number"
                            value={form.data.year}
                            onChange={(event) =>
                                form.setData('year', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.year} />
                    </div>
                    <div className="flex items-end">
                        <Button type="submit" disabled={form.processing}>
                            Ouvrir la période
                        </Button>
                    </div>
                </form>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Période</th>
                                <th className="p-3">Statut</th>
                                <th className="p-3">Bulletins</th>
                                <th className="p-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {periods.length === 0 && (
                                <tr>
                                    <td
                                        className="text-muted-foreground p-3"
                                        colSpan={4}
                                    >
                                        Aucune période.
                                    </td>
                                </tr>
                            )}
                            {periods.map((period) => (
                                <tr key={period.id} className="border-t">
                                    <td className="p-3">{period.label}</td>
                                    <td className="p-3">
                                        {period.status_label}
                                    </td>
                                    <td className="p-3">
                                        {period.payslips_count}
                                    </td>
                                    <td className="p-3">
                                        {period.can_run && (
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        `/paie/${period.id}/lancer`,
                                                    )
                                                }
                                            >
                                                Lancer la paie
                                            </Button>
                                        )}
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

PayPeriodsIndex.layout = {
    breadcrumbs: [{ title: 'Paie', href: '/paie' }],
};
