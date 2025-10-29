<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import de Colaboradores Finalizado</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin: 20px 0;
        }
        .status-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-error {
            background-color: #fef2f2;
            color: #dc2626;
        }
        .status-warning {
            background-color: #fef3c7;
            color: #d97706;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }
        .stat-card {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #e2e8f0;
        }
        .stat-card.success {
            border-left-color: #10b981;
        }
        .stat-card.error {
            border-left-color: #ef4444;
        }
        .stat-card.total {
            border-left-color: #3b82f6;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 5px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-fill {
            height: 100%;
            background-color: #10b981;
            transition: width 0.3s ease;
        }
        .error-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
        }
        .error-title {
            color: #dc2626;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            font-size: 14px;
        }
        .error-item {
            margin-bottom: 10px;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #ef4444;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📊 Sistema Convenia</div>
            <h1>Import de Colaboradores Finalizado</h1>
            
            @if($isSuccess && !$hasErrors)
                <div class="status-badge status-success">
                    ✅ Concluído com Sucesso
                </div>
            @elseif($hasErrors && $successfulRows > 0)
                <div class="status-badge status-warning">
                    ⚠️ Concluído com Avisos
                </div>
            @else
                <div class="status-badge status-error">
                    ❌ Falha no Processamento
                </div>
            @endif
        </div>

        <p>Olá <strong>{{ $user->name }}</strong>,</p>
        
        <p>O processamento do seu arquivo de import de colaboradores foi finalizado.</p>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number">{{ $totalRows ?? 0 }}</div>
                <div class="stat-label">Total de Linhas</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number">{{ $successfulRows ?? 0 }}</div>
                <div class="stat-label">Sucessos</div>
            </div>
            <div class="stat-card error">
                <div class="stat-number">{{ $failedRows ?? 0 }}</div>
                <div class="stat-label">Falhas</div>
            </div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $progressPercentage ?? 100 }}%"></div>
        </div>
        <p style="text-align: center; color: #6b7280; font-size: 14px;">
            Progresso: {{ number_format($progressPercentage ?? 100, 1) }}%
        </p>

        @if($hasErrors && !empty($errors))
            <div class="error-section">
                <div class="error-title">Erros Encontrados:</div>
                <div class="error-list">
                    @foreach($errors as $lineNumber => $lineErrors)
                        <div class="error-item">
                            <strong>Linha {{ $lineNumber }}:</strong>
                            @if(is_array($lineErrors))
                                <ul style="margin: 5px 0 0 20px;">
                                    @foreach($lineErrors as $field => $fieldErrors)
                                        @if(is_array($fieldErrors))
                                            @foreach($fieldErrors as $error)
                                                <li>{{ $field }}: {{ $error }}</li>
                                            @endforeach
                                        @else
                                            <li>{{ $field }}: {{ $fieldErrors }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                {{ $lineErrors }}
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($isSuccess && !$hasErrors)
            <p style="color: #059669; font-weight: 600;">
                🎉 Todos os colaboradores foram importados com sucesso!
            </p>
        @elseif($hasErrors && $successfulRows > 0)
            <p style="color: #d97706; font-weight: 600;">
                ⚠️ {{ $successfulRows }} colaboradores foram importados, mas {{ $failedRows }} registros tiveram problemas.
            </p>
        @else
            <p style="color: #dc2626; font-weight: 600;">
                ❌ Não foi possível importar nenhum colaborador. Verifique o arquivo e tente novamente.
            </p>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/collaborators" class="button">
                Ver Colaboradores
            </a>
        </div>

        <div class="footer">
            <p>Este email foi enviado automaticamente pelo Sistema Convenia.</p>
            <p>Data: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Import ID: #{{ $import->id }}</p>
        </div>
    </div>
</body>
</html>