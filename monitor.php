<?php
/**
 * Agente de Monitoramento de Hardware - Helpdesk Prefeitura
 * Rodando em loop de segundo plano.
 */

// =========================================================================
// CARREGAMENTO DE CONFIGURAÇÕES LOCAIS (config.json)
// =========================================================================
$configFile = __DIR__ . '/config.json';

function carregarConfiguracao(string $filePath): array {
    $default = [
        'api_url'         => 'http://192.168.1.11/helpdesk_prefeitura/api/telemetria.php',
        'api_token'       => 'BORBOREMA_SECURE_TOKEN_2026',
        'hostname_custom' => getenv('COMPUTERNAME') ?: 'DESCONHECIDO',
        'setor_nome'      => 'Geral',
        'intervalo'       => 60
    ];

    if (file_exists($filePath)) {
        $json = json_decode(file_get_contents($filePath), true);
        if (is_array($json)) {
            return array_merge($default, $json);
        }
    }

    return $default;
}

// =========================================================================
// FUNÇÕES AUXILIARES DE COLETA E ENVIO
// =========================================================================

function getPsData(string $command): array {
    $cmd = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"$command | ConvertTo-Json -Compress\"";
    $output = @shell_exec($cmd);
    if (!$output) return [];
    
    $data = json_decode(trim($output), true);
    return is_array($data) ? $data : [];
}

function coletarDadosEAnalisar(array $config): array {
    $hostname  = !empty($config['hostname_custom']) ? $config['hostname_custom'] : (getenv('COMPUTERNAME') ?: 'DESCONHECIDO');
    $setorNome = !empty($config['setor_nome']) ? $config['setor_nome'] : 'Geral';
    
    $cpuInfo = getPsData("Get-CimInstance -ClassName Win32_Processor");
    $cpuModelo = $cpuInfo['Name'] ?? 'Processador Desconhecido';

    $gpuInfo = getPsData("Get-CimInstance -ClassName Win32_VideoController");
    $gpuModelo = isset($gpuInfo[0]) ? $gpuInfo[0]['Name'] : ($gpuInfo['Name'] ?? 'Placa de Vídeo Desconhecida');

    $osInfo = getPsData("Get-CimInstance -ClassName Win32_OperatingSystem");
    $ramTotalMB = round(($osInfo['TotalVisibleMemorySize'] ?? 0) / 1024);
    $ramLivreMB = round(($osInfo['FreePhysicalMemory'] ?? 0) / 1024);

    $diskInfo = getPsData("Get-CimInstance -ClassName Win32_LogicalDisk -Filter \"DeviceID='C:'\"");
    $discoLivreGB = round(($diskInfo['FreeSpace'] ?? 0) / (1024 * 1024 * 1024));

    $alertas = [];

    if ($ramTotalMB > 0 && ($ramLivreMB / $ramTotalMB) < 0.10) {
        $alertas[] = 'A memoria ram esta quase cheia';
    }

    if ($discoLivreGB < 5) {
        $alertas[] = 'O disco esta Quase cheio';
    }

    return [
        'token'          => $config['api_token'],
        'hostname'       => trim($hostname),
        'setor_nome'     => trim($setorNome),
        'cpu_modelo'     => trim($cpuModelo),
        'gpu_modelo'     => trim($gpuModelo),
        'ram_total_mb'   => $ramTotalMB,
        'ram_livre_mb'   => $ramLivreMB,
        'disco_livre_gb' => $discoLivreGB,
        'alertas'        => $alertas
    ];
}

function enviarParaAPI(array $payload, string $apiUrl, string $apiToken): bool {
    $ch = curl_init($apiUrl);
    
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiToken
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // Grava log detalhado do retorno da API
    $logMsg = sprintf(
        "[%s] HTTP: %d | Erro cURL: %s | Resposta API: %s\n",
        date('Y-m-d H:i:s'),
        $httpCode,
        $curlErr ?: 'Nenhum',
        $response ?: 'Sem resposta'
    );
    file_put_contents(__DIR__ . '/agente_erros.log', $logMsg, FILE_APPEND);

    return ($httpCode === 200 || $httpCode === 201);
}

// =========================================================================
// LOOP PRINCIPAL DE EXECUÇÃO
// =========================================================================
while (true) {
    try {
        $config = carregarConfiguracao($configFile);
        $dados  = coletarDadosEAnalisar($config);
        enviarParaAPI($dados, $config['api_url'], $config['api_token']);

        $intervalo = isset($config['intervalo']) ? (int)$config['intervalo'] : 60;
    } catch (Throwable $e) {
        error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", 3, __DIR__ . '/agente_erros.log');
        $intervalo = 60;
    }

    gc_collect_cycles();
    sleep($intervalo);
}