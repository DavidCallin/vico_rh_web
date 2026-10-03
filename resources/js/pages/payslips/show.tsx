import { Head, usePage } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import Heading from '@/components/heading';
import { formatDate, formatXof } from '@/lib/hr';

type Line = {
    id: number;
    code: string;
    name: string;
    type: string;
    amount: string;
    quantity: string;
    is_locked: boolean;
};

type Payslip = {
    id: number;
    employee_name: string;
    matricule: string;
    position: string | null;
    period: string;
    generation_date: string;
    gross_salary: string;
    total_earnings: string;
    total_deductions: string;
    net_salary: string;
    lines: Line[];
};

export default function PayslipShow({ payslip }: { payslip: Payslip }) {
    const { name } = usePage().props;

    return (
        <>
            <Head title={`Bulletin ${payslip.period}`} />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={`Bulletin de paie ${payslip.period}`}
                    description="Montants en francs CFA (XOF)."
                />
                <article className="rounded-xl border p-4">
                    <p className="text-sm">{name}</p>
                    <h2 className="mt-2 text-lg font-semibold">
                        {payslip.employee_name}
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Matricule {payslip.matricule}
                        {payslip.position ? ` · ${payslip.position}` : ''}
                        {' · '}
                        Émis le {formatDate(payslip.generation_date)}
                    </p>

                    <table className="mt-4 w-full text-sm">
                        <thead className="text-left">
                            <tr className="border-b">
                                <th className="py-2">Rubrique</th>
                                <th className="py-2">Quantité</th>
                                <th className="py-2 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            {payslip.lines.map((line) => (
                                <tr key={line.id} className="border-b">
                                    <td className="py-2">
                                        {line.name}
                                        {line.is_locked && (
                                            <span className="text-muted-foreground ml-2 inline-flex items-center gap-1 text-xs">
                                                <Lock className="size-3" />
                                                Calculé automatiquement, non
                                                modifiable
                                            </span>
                                        )}
                                    </td>
                                    <td className="py-2">{line.quantity}</td>
                                    <td className="py-2 text-right">
                                        {line.type === 'gain' ? '' : '− '}
                                        {formatXof(line.amount)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <dl className="mt-4 grid gap-1 text-sm">
                        <div className="flex justify-between">
                            <dt>Total des gains</dt>
                            <dd>{formatXof(payslip.total_earnings)}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt>Brut imposable</dt>
                            <dd>{formatXof(payslip.gross_salary)}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt>Total des retenues</dt>
                            <dd>{formatXof(payslip.total_deductions)}</dd>
                        </div>
                        <div className="flex justify-between text-base font-semibold">
                            <dt>Net à payer</dt>
                            <dd>{formatXof(payslip.net_salary)}</dd>
                        </div>
                    </dl>
                </article>
            </div>
        </>
    );
}

PayslipShow.layout = {
    breadcrumbs: [
        { title: 'Bulletins', href: '/bulletins' },
        { title: 'Bulletin', href: '/bulletins' },
    ],
};
