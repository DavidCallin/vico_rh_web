<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrat de travail</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2933; }
        h1 { font-size: 18px; margin-bottom: 0; }
        h2 { font-size: 14px; margin-top: 22px; }
        p { line-height: 1.45; }
        .muted { color: #52606d; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 4px 0; vertical-align: top; }
        td:first-child { width: 38%; color: #52606d; }
        .sign { margin-top: 36px; }
        .sign td { width: 50%; height: 90px; }
    </style>
</head>
<body>
    @php
        $types = [
            'cdi' => 'contrat à durée indéterminée',
            'cdd' => 'contrat à durée déterminée',
            'internship' => 'convention de stage',
            'interim' => 'contrat d\'intérim',
            'apprenticeship' => 'contrat d\'apprentissage',
        ];
        $typeLabel = $types[$contract->contract_type] ?? $contract->contract_type;
        $salary = number_format((float) $contract->base_salary, 0, ',', ' ');
    @endphp

    <h1>Contrat de travail</h1>
    <p class="muted">{{ $employer['city'] ?? 'Cotonou' }}, le {{ now()->format('d/m/Y') }}</p>

    <h2>Entre les soussignés</h2>
    <p>
        <strong>{{ $employer['name'] }}</strong>,
        {{ $employer['address'] }},
        ci-après « l'employeur »,
    </p>
    <p>et</p>
    <p>
        <strong>{{ $contract->employee->full_name }}</strong>,
        matricule {{ $contract->employee->matricule }},
        @if($contract->employee->address) demeurant {{ $contract->employee->address }}, @endif
        ci-après « le salarié ».
    </p>

    <h2>Engagement</h2>
    <p>
        L'employeur engage le salarié dans le cadre d'un {{ $typeLabel }}.
        @if($contract->employee->position)
            Le poste occupé est : {{ $contract->employee->position->title }}.
        @endif
    </p>

    <table>
        <tr>
            <td>Date de début</td>
            <td>{{ $contract->start_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Date de fin</td>
            <td>{{ $contract->end_date ? $contract->end_date->format('d/m/Y') : 'Non applicable (durée indéterminée)' }}</td>
        </tr>
        <tr>
            <td>Salaire de base mensuel</td>
            <td>{{ $salary }} F CFA</td>
        </tr>
        <tr>
            <td>Lieu de travail</td>
            <td>{{ $contract->employee->site ?: ($employer['city'] ?? 'Cotonou') }}</td>
        </tr>
    </table>

    <h2>Rémunération et durée</h2>
    <p>
        Le salaire de base indiqué ci-dessus est exprimé en francs CFA.
        Il sert de base au bulletin de paie. Les retenues légales, notamment
        l'impôt sur les traitements et salaires, sont calculées lors de chaque
        période de paie.
    </p>
    <p>
        Le salarié observe l'horaire communiqué par l'employeur et bénéficie
        des congés prévus par le Code du travail de la République du Bénin.
    </p>

    <h2>Documents</h2>
    <p>
        Ce document est généré automatiquement à partir du dossier salarié.
        Il constate les éléments enregistrés dans l'application. Il ne remplace
        pas un conseil juridique ni les mentions imposées par une convention
        particulière.
    </p>

    <table class="sign">
        <tr>
            <td>L'employeur<br>{{ $employer['name'] }}</td>
            <td>Le salarié<br>{{ $contract->employee->full_name }}</td>
        </tr>
    </table>
</body>
</html>
