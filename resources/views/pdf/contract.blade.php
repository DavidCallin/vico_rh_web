<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; line-height: 1.6; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .label { font-weight: bold; color: #2563eb; }
        .signature { margin-top: 50px; display: flex; justify-content: space-between; }
        .sign-box { width: 45%; border-top: 1px solid #333; padding-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CONTRAT DE TRAVAIL - {{ strtoupper($contract->contract_type) }}</h1>
    </div>

    <div class="section">
        <p>Entre la société <strong>VICO RH</strong>, et :</p>
        <p>
            <span class="label">Nom et Prénom :</span> {{ $contract->employee->full_name }}<br>
            <span class="label">Matricule :</span> {{ $contract->employee->matricule }}<br>
            <span class="label">Adresse :</span> {{ $contract->employee->address ?? 'Non renseignée' }}
        </p>
    </div>

    <div class="section">
        <p class="label">ARTICLE 1 : TYPE DE CONTRAT</p>
        <p>Le présent contrat est un contrat à {{ $contract->contract_type === 'cdi' ? 'Durée Indéterminée (CDI)' : 'Durée Déterminée (CDD)' }}.</p>
        <p><span class="label">Date de début :</span> {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</p>
        @if($contract->end_date)
            <p><span class="label">Date de fin :</span> {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</p>
        @endif
    </div>

    <div class="section">
        <p class="label">ARTICLE 2 : RÉMUNÉRATION</p>
        <p>En contrepartie de son travail, le salarié percevra un salaire mensuel brut de :</p>
        <p style="font-size: 16px; font-weight: bold; text-align: center; background: #f0f9ff; padding: 10px;">
            {{ number_format($contract->base_salary, 2, ',', ' ') }} €
        </p>
    </div>

    <div class="signature">
        <div class="sign-box">
            <p>L'Employeur</p>
            <p>(Signature et Cachet)</p>
        </div>
        <div class="sign-box">
            <p>Le Salarié</p>
            <p>(Lu et approuvé)</p>
        </div>
    </div>
</body>
</html>