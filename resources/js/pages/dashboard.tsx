import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { formatDate, formatXof } from '@/lib/hr';
import { dashboard } from '@/routes';

type ExpiringContract = {
    id: number;
    employee_name: string;
    contract_type_label: string;
    end_date: string | null;
    expired: boolean;
};

type RecentPayslip = {
    id: number;
    period: string;
    net_salary: string;
    employee_name: string | null;
};

export default function Dashboard({
    isHr,
    pendingLeaves,
    pendingAbsences,
    payslipCount,
    expiringContracts,
    recentPayslips,
}: {
    isHr: boolean;
    pendingLeaves: number;
    pendingAbsences: number;
    payslipCount: number;
    expiringContracts: ExpiringContract[];
    recentPayslips: RecentPayslip[];
}) {
    return (
        <>
            <Head title="Tableau de bord" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Tableau de bord"
                    description="Congés, absences, paie et contrats de l'entreprise."
                />

                <div className="grid gap-4 md:grid-cols-3">
                    <Summary
                        label="Congés en attente"
                        value={pendingLeaves}
                        href="/conges"
                    />
                    <Summary
                        label="Absences en attente"
                        value={pendingAbsences}
                        href="/absences"
                    />
                    <Summary
                        label="Bulletins"
                        value={payslipCount}
                        href="/bulletins"
                    />
                </div>

                <section className="rounded-xl border p-4">
                    <h2 className="font-semibold">
                        Contrats échus ou bientôt échus
                    </h2>
                    {expiringContracts.length === 0 ? (
                        <p className="text-muted-foreground mt-2 text-sm">
                            Aucun contrat dans la fenêtre de rappel.
                        </p>
                    ) : (
                        <ul className="mt-3 space-y-2 text-sm">
                            {expiringContracts.map((contract) => (
                                <li key={contract.id}>
                                    <span className="font-medium">
                                        {contract.employee_name}
                                    </span>
                                    {' · '}
                                    {contract.contract_type_label}
                                    {' · '}
                                    {formatDate(contract.end_date)}
                                    {' · '}
                                    <span
                                        className={
                                            contract.expired
                                                ? 'text-red-600'
                                                : 'text-amber-700'
                                        }
                                    >
                                        {contract.expired
                                            ? 'Échu'
                                            : 'Bientôt échu'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                    <Button asChild variant="outline" className="mt-4">
                        <Link href="/contrats">Voir les contrats</Link>
                    </Button>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="font-semibold">Derniers bulletins</h2>
                    {recentPayslips.length === 0 ? (
                        <p className="text-muted-foreground mt-2 text-sm">
                            {isHr
                                ? 'Lancez une période de paie pour produire les bulletins.'
                                : 'Aucun bulletin pour le moment.'}
                        </p>
                    ) : (
                        <ul className="mt-3 space-y-2 text-sm">
                            {recentPayslips.map((payslip) => (
                                <li key={payslip.id}>
                                    <Link
                                        href={`/bulletins/${payslip.id}`}
                                        className="underline"
                                    >
                                        {payslip.period}
                                        {payslip.employee_name
                                            ? ` · ${payslip.employee_name}`
                                            : ''}
                                        {' · '}
                                        {formatXof(payslip.net_salary)}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

function Summary({
    label,
    value,
    href,
}: {
    label: string;
    value: number;
    href: string;
}) {
    return (
        <Link href={href} className="hover:bg-muted/40 rounded-xl border p-4">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-2 text-3xl font-semibold">{value}</p>
        </Link>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Tableau de bord',
            href: dashboard(),
        },
    ],
};
