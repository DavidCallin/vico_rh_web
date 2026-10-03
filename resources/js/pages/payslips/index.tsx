import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { formatXof } from '@/lib/hr';

type PayslipRow = {
    id: number;
    employee_name: string;
    period: string;
    gross_salary: string;
    net_salary: string;
};

export default function PayslipsIndex({
    payslips,
    isHr,
}: {
    payslips: PayslipRow[];
    isHr: boolean;
}) {
    return (
        <>
            <Head title="Bulletins" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Bulletins de paie"
                    description={
                        isHr
                            ? 'Tous les bulletins émis.'
                            : 'Vos bulletins, également envoyés par e-mail.'
                    }
                />
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                {isHr && <th className="p-3">Salarié</th>}
                                <th className="p-3">Période</th>
                                <th className="p-3">Brut imposable</th>
                                <th className="p-3">Net à payer</th>
                            </tr>
                        </thead>
                        <tbody>
                            {payslips.length === 0 && (
                                <tr>
                                    <td
                                        className="text-muted-foreground p-3"
                                        colSpan={isHr ? 4 : 3}
                                    >
                                        Aucun bulletin.
                                    </td>
                                </tr>
                            )}
                            {payslips.map((payslip) => (
                                <tr key={payslip.id} className="border-t">
                                    {isHr && (
                                        <td className="p-3">
                                            {payslip.employee_name}
                                        </td>
                                    )}
                                    <td className="p-3">
                                        <Link
                                            href={`/bulletins/${payslip.id}`}
                                            className="underline"
                                        >
                                            {payslip.period}
                                        </Link>
                                    </td>
                                    <td className="p-3">
                                        {formatXof(payslip.gross_salary)}
                                    </td>
                                    <td className="p-3">
                                        {formatXof(payslip.net_salary)}
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

PayslipsIndex.layout = {
    breadcrumbs: [{ title: 'Bulletins', href: '/bulletins' }],
};
