<?php
// C:\MeuMonitorAgent\setup.php

$configFile = __DIR__ . '/config.json';
$msg = '';
$msgType = '';

// Defina a URL da sua API para buscar os setores
$apiUrlSetores = "http://SEU_SERVIDOR_HELPDESK/api/setores.php"; // Ou ajuste para sua rota de setores

// Se enviou o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostnameCustom = trim($_POST['hostname_custom'] ?? '');
    $setorId        = !empty($_POST['setor_id']) ? (int)$_POST['setor_id'] : null;
    $apiUrl         = trim($_POST['api_url'] ?? '');
    $apiToken       = trim($_POST['api_token'] ?? 'SEU_TOKEN_MUITO_SEGURO_AQUI');

    if (empty($hostnameCustom) || empty($setorId)) {
        $msg = "Por favor, preencha o Nome da Máquina e escolha um Setor.";
        $msgType = "error";
    } else {
        $configData = [
            'api_url'         => $apiUrl,
            'api_token'       => $apiToken,
            'hostname_custom' => $hostnameCustom,
            'setor_id'        => $setorId
        ];

        if (file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT))) {
            $msg = "Configuração salva com sucesso! Você pode fechar esta aba.";
            $msgType = "success";
        } else {
            $msg = "Erro ao gravar o arquivo config.json. Verifique as permissões.";
            $msgType = "error";
        }
    }
}

// Tenta buscar os setores ativos via API para popular o <select>
$setores = [];
$ch = curl_init("http://localhost/helpdesk/api/telemetria.php?acao=listar_setores"); // Ajuste o IP/Domínio do seu Servidor
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$res = curl_exec($ch);
curl_close($ch);

if ($res) {
    $json = json_decode($res, true);
    if (isset($json['setores'])) {
        $setores = $json['setores'];
    }
}

// Hostname padrão da máquina como sugestão
$hostnamePadrao = gethostname();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configuração do Agente - MeuMonitorAgent</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 12px; width: 100%; max-width: 420px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h2 { margin-top: 0; color: #38bdf8; font-size: 1.4rem; text-align: center; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: #94a3b8; }
        input, select { width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #fff; font-size: 0.95rem; }
        input:focus, select:focus { outline: none; border-color: #38bdf8; }
        button { width: 100%; padding: 0.75rem; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: 0.2s; }
        button:hover { background: #0369a1; }
        .alert { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; text-align: center; }
        .alert.error { background: #7f1d1d; color: #fca5a5; }
        .alert.success { background: #14532d; color: #86efac; }
    </style>
</head>
<body>

<div class="card">
    <h2> Setup do Agente </h2>

    <?php if ($msg): ?>
        <div class="alert <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="hostname_custom">Nome Personalizado / Tag da Máquina:</label>
            <input type="text" id="hostname_custom" name="hostname_custom" value="<?= htmlspecialchars($hostnamePadrao) ?>" required>
        </div>

        <div class="form-group">
            <label for="setor_id">Setor de Alocação:</label>
            <select id="setor_id" name="setor_id" required>
                <option value="">-- Selecione o Setor --</option>
                <?php if (!empty($setores)): ?>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?> (<?= htmlspecialchars($setor['sigla']) ?>)</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback caso a API esteja offline no momento do setup -->
                    <option value="1">1 - Administração / TI</option>
                    <option value="2">2 - Educação (SEDUC)</option>
                    <option value="5">5 - TI Prefeitura</option>
                    <option value="6">6 - Almoxarifado</option>
                    <option value="7">7 - Obras e Postura</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="api_url">URL da API Central:</label>
            <input type="url" id="api_url" name="api_url" value="http://192.168.1.100/helpdesk/api/telemetria.php" required>
        </div>

        <button type="submit">Salvar e Registrar Agente</button>
    </form>
</div>

</body>
</html>