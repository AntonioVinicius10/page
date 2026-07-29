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

/**
 * Executa comandos PowerShell com retorno em array associativo
 */
function getPsData(string $command): array {
    $cmd = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"$command | ConvertTo-Json -Compress\"";
    $output = @shell_exec($cmd);
    if (!$output) return [];
    
    $data = json_decode(trim($output), true);
    return is_array($data) ? $data : [];
}

/**
 * Coleta os dados de hardware do sistema e analisa anomalias
 */
function coletarDadosEAnalisar(array $config): array {
    // 1. Identificação personalizada (Setup) ou padrão do Windows
    $hostname  = !empty($config['hostname_custom']) ? $config['hostname_custom'] : (getenv('COMPUTERNAME') ?: 'DESCONHECIDO');
    $setorNome = !empty($config['setor_nome']) ? $config['setor_nome'] : 'Geral';
    
    // 2. Modelo do Processador (CPU)
    $cpuInfo = getPsData("Get-CimInstance -ClassName Win32_Processor");
    $cpuModelo = $cpuInfo['Name'] ?? 'Processador Desconhecido';

    // 3. Modelo da Placa de Vídeo (GPU)
    $gpuInfo = getPsData("Get-CimInstance -ClassName Win32_VideoController");
    $gpuModelo = isset($gpuInfo[0]) ? $gpuInfo[0]['Name'] : ($gpuInfo['Name'] ?? 'Placa de Vídeo Desconhecida');

    // 4. Memória RAM (Total e Livre em MB)
    $osInfo = getPsData("Get-CimInstance -ClassName Win32_OperatingSystem");
    $ramTotalMB = round(($osInfo['TotalVisibleMemorySize'] ?? 0) / 1024);
    $ramLivreMB = round(($osInfo['FreePhysicalMemory'] ?? 0) / 1024);

    // 5. Espaço Livre no Disco Principal (C:)
    $diskInfo = getPsData("Get-CimInstance -ClassName Win32_LogicalDisk -Filter \"DeviceID='C:'\"");
    $discoLivreGB = round(($diskInfo['FreeSpace'] ?? 0) / (1024 * 1024 * 1024));

    // 6. Regras de Detecção de Problemas
    $alertas = [];

    // Alerta: Menos de 10% de RAM disponível
    if ($ramTotalMB > 0 && ($ramLivreMB / $ramTotalMB) < 0.10) {
        $alertas[] = 'CRITICAL_LOW_RAM';
    }

    // Alerta: Menos de 5GB de espaço no disco C:
    if ($discoLivreGB < 5) {
        $alertas[] = 'CRITICAL_LOW_DISK';
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

/**
 * Envia o payload via cURL HTTP POST para o endpoint API
 */
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
    curl_close($ch);

    return ($httpCode === 200 || $httpCode === 201);
}

// =========================================================================
// LOOP PRINCIPAL DE EXECUÇÃO
// =========================================================================
while (true) {
    try {
        // Recarrega as configurações a cada ciclo (permite atualizar sem reiniciar o agente)
        $config = carregarConfiguracao($configFile);

        $dados = coletarDadosEAnalisar($config);
        enviarParaAPI($dados, $config['api_url'], $config['api_token']);

        $intervalo = isset($config['intervalo']) ? (int)$config['intervalo'] : 60;
    } catch (Throwable $e) {
        // Registra log local silencioso se houver falha de rede ou execução
        error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", 3, __DIR__ . '/agente_erros.log');
        $intervalo = 60;
    }

    // Libera a memória utilizada na iteração e aguarda o intervalo configurado
    gc_collect_cycles();
    sleep($intervalo);
}