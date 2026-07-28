<?php
/**
 * Agente de Monitoramento de Hardware - Helpdesk Prefeitura
 * Rodando em loop de segundo plano.
 */

// =========================================================================
// CONFIGURAÇÕES DO AGENTE
// =========================================================================
// Altere o IP/Domínio para a URL real do seu sistema web
define('API_URL', 'http://192.168.1.11/helpdesk_prefeitura/api/telemetria.php'); 
define('API_TOKEN', 'BORBOREMA_SECURE_TOKEN_2026'); // Token de validação
define('INTERVALO_SEGUNDOS', 60);                    // Intervalo de coleta (60 segundos)

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
function coletarDadosEAnalisar(): array {
    // 1. Nome do computador no Windows
    $hostname = getenv('COMPUTERNAME') ?: 'DESCONHECIDO';
    
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
        'hostname'       => $hostname,
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
function enviarParaAPI(array $payload): bool {
    $ch = curl_init(API_URL);
    
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . API_TOKEN
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
        $dados = coletarDadosEAnalisar();
        enviarParaAPI($dados);
    } catch (Throwable $e) {
        // Registra log local silencioso se houver falha de rede ou execução
        error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", 3, __DIR__ . '/agente_erros.log');
    }

    // Libera a memória utilizada na iteração e aguarda o intervalo configurado
    gc_collect_cycles();
    sleep(INTERVALO_SEGUNDOS);
}