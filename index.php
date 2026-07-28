<?php
// C:\MeuMonitorAgent\setup.php

$configFile = __DIR__ . '/config.json';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostnameCustom = trim($_POST['hostname_custom'] ?? '');
    $setorNome      = trim($_POST['setor_nome'] ?? '');
    $apiUrl         = trim($_POST['api_url'] ?? '');
    $apiToken       = trim($_POST['api_token'] ?? 'SEU_TOKEN_MUITO_SEGURO_AQUI');

    if (empty($hostnameCustom) || empty($setorNome)) {
        $msg = "Por favor, preencha o Nome da Máquina e o Setor.";
        $msgType = "error";
    } else {
        $configData = [
            'api_url'         => $apiUrl,
            'api_token'       => $apiToken,
            'hostname_custom' => $hostnameCustom,
            'setor_nome'      => $setorNome
        ];

        if (file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT))) {
            $msg = "Configuração salva com sucesso! O monitor enviará estes dados na próxima execução.";
            $msgType = "success";
        } else {
            $msg = "Erro ao gravar o arquivo config.json.";
            $msgType = "error";
        }
    }
}

$hostnamePadrao = gethostname();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Setup do Agente - MeuMonitorAgent</title>
    <style>
        * { box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 12px; width: 100%; max-width: 420px; border: 1px solid #334155; }
        h2 { margin-top: 0; color: #38bdf8; text-align: center; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: #94a3b8; }
        input { width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #fff; }
        button { width: 100%; padding: 0.75rem; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .alert { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; font-size: 0.9rem; }
        .alert.error { background: #7f1d1d; color: #fca5a5; }
        .alert.success { background: #14532d; color: #86efac; }
    </style>
</head>
<body>

<div class="card">
    <h2>Setup do Agente</h2>

    <?php if ($msg): ?>
        <div class="alert <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="hostname_custom">Nome Personalizado / Tag do PC:</label>
            <input type="text" id="hostname_custom" name="hostname_custom" value="<?= htmlspecialchars($hostnamePadrao) ?>" required>
        </div>

        <div class="form-group">
            <label for="setor_nome">Setor de Alocação (Digite o Setor):</label>
            <input type="text" id="setor_nome" name="setor_nome" placeholder="Ex: Recepção, Obras, Financeiro..." required>
        </div>

        <div class="form-group">
            <label for="api_url">URL da API Central:</label>
            <input type="url" id="api_url" name="api_url" value="http://localhost/helpdesk/api/telemetria.php" required>
        </div>

        <button type="submit">Salvar Configuração</button>
    </form>
</div>

</body>
</html>