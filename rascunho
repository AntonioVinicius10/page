<?php
/**
 * Agente de Monitoramento de Hardware - Helpdesk Prefeitura (Modo CPU-Z Avançado)
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

/**
 * Traduz o código numérico de tipo de memória do WMI
 */
function traduzirTipoRam(int $smbiosType): string {
    $tipos = [
        20 => 'DDR', 21 => 'DDR2', 22 => 'DDR2 FB-DIMM',
        24 => 'DDR3', 26 => 'DDR4', 34 => 'DDR5'
    ];
    return $tipos[$smbiosType] ?? 'Desconhecido/Outro';
}

function coletarDadosEAnalisar(array $config): array {
    $hostname  = !empty($config['hostname_custom']) ? $config['hostname_custom'] : (getenv('COMPUTERNAME') ?: 'DESCONHECIDO');
    $setorNome = !empty($config['setor_nome']) ? $config['setor_nome'] : 'Geral';

    // 1. PROCESSADOR (CPU)
    $cpuInfo = getPsData("Get-CimInstance -ClassName Win32_Processor");
    $cpuModelo      = $cpuInfo['Name'] ?? 'Processador Desconhecido';
    $cpuCores       = $cpuInfo['NumberOfCores'] ?? 0;
    $cpuThreads     = $cpuInfo['NumberOfLogicalProcessors'] ?? 0;
    $cpuClockMaxMHz = $cpuInfo['MaxClockSpeed'] ?? 0;
    $cpuSocket      = $cpuInfo['SocketDesignation'] ?? 'N/A';

    // 2. PLACA-MÃE (Motherboard / Mainboard)
    $boardInfo = getPsData("Get-CimInstance -ClassName Win32_BaseBoard");
    $moboFabricante = $boardInfo['Manufacturer'] ?? 'Desconhecido';
    $moboModelo     = $boardInfo['Product'] ?? 'Desconhecido';

    $biosInfo = getPsData("Get-CimInstance -ClassName Win32_BIOS");
    $biosVersao = $biosInfo['SMBIOSBIOSVersion'] ?? ($biosInfo['Version'] ?? 'N/A');

    // 3. PLACA DE VÍDEO (GPU)
    $gpuInfo = getPsData("Get-CimInstance -ClassName Win32_VideoController");
    // Trata caso venha array (notebooks com GPU integrada + dedicada)
    $gpuPrincipal = isset($gpuInfo[0]) ? $gpuInfo[0] : $gpuInfo;
    $gpuModelo = $gpuPrincipal['Name'] ?? 'Placa de Vídeo Desconhecida';
    $gpuVramMB = round(($gpuPrincipal['AdapterRAM'] ?? 0) / (1024 * 1024));

    // 4. MEMÓRIA RAM DETALHADA
    $osInfo     = getPsData("Get-CimInstance -ClassName Win32_OperatingSystem");
    $ramTotalMB = round(($osInfo['TotalVisibleMemorySize'] ?? 0) / 1024);
    $ramLivreMB = round(($osInfo['FreePhysicalMemory'] ?? 0) / 1024);

    $ramPentesInfo = getPsData("Get-CimInstance -ClassName Win32_PhysicalMemory");
    // Se for apenas 1 pente, engloba em array para manter o padrão
    if (isset($ramPentesInfo['Capacity'])) {
        $ramPentesInfo = [$ramPentesInfo];
    }

    $pentesCount = count($ramPentesInfo);
    $ramClockMHz = 0;
    $ramTipo     = 'Desconhecido';

    if ($pentesCount > 0) {
        $ramClockMHz = $ramPentesInfo[0]['Speed'] ?? 0;
        $ramTipo = traduzirTipoRam($ramPentesInfo[0]['SMBIOSMemoryType'] ?? 0);
    }

    // 5. ARMAZENAMENTO (DISCO C:)
    $diskInfo     = getPsData("Get-CimInstance -ClassName Win32_LogicalDisk -Filter \"DeviceID='C:'\"");
    $discoTotalGB = round(($diskInfo['Size'] ?? 0) / (1024 * 1024 * 1024));
    $discoLivreGB = round(($diskInfo['FreeSpace'] ?? 0) / (1024 * 1024 * 1024));

    // 6. SISTEMA OPERACIONAL E REDE
    $osNome  = $osInfo['Caption'] ?? 'Windows OS';
    $osArch  = $osInfo['OSArchitecture'] ?? '64-bit';

    $netInfo = getPsData("Get-CimInstance -ClassName Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled -eq \$true }");
    $netActive = isset($netInfo[0]) ? $netInfo[0] : $netInfo;
    $ipArray   = $netActive['IPAddress'] ?? [];
    $ipLocal   = is_array($ipArray) ? ($ipArray[0] ?? '127.0.0.1') : $ipArray;
    $macAddr   = $netActive['MACAddress'] ?? '00:00:00:00:00:00';

    // 7. ALERTAS
    $alertas = [];
    if ($ramTotalMB > 0 && ($ramLivreMB / $ramTotalMB) < 0.10) {
        $alertas[] = 'A memoria ram esta quase cheia';
    }
    if ($discoLivreGB < 5) {
        $alertas[] = 'O disco esta Quase cheio';
    }

    // PAYLOAD "CPU-Z STYLE" COMPLETO
    return [
        'token'           => $config['api_token'],
        'hostname'        => trim($hostname),
        'setor_nome'      => trim($setorNome),
        'ip_local'        => $ipLocal,
        'mac_address'     => $macAddr,
        'os_nome'         => trim($osNome) . " (" . $osArch . ")",
        
        // Dados de CPU
        'cpu_modelo'      => trim($cpuModelo),
        'cpu_cores'       => $cpuCores,
        'cpu_threads'     => $cpuThreads,
        'cpu_clock_mhz'   => $cpuClockMaxMHz,
        'cpu_socket'      => $cpuSocket,

        // Dados de Placa-Mãe
        'mobo_fabricante' => trim($moboFabricante),
        'mobo_modelo'     => trim($moboModelo),
        'bios_versao'     => trim($biosVersao),

        // Dados de GPU
        'gpu_modelo'      => trim($gpuModelo),
        'gpu_vram_mb'     => $gpuVramMB,

        // Dados de RAM
        'ram_total_mb'    => $ramTotalMB,
        'ram_livre_mb'    => $ramLivreMB,
        'ram_tipo'        => $ramTipo,
        'ram_clock_mhz'   => $ramClockMHz,
        'ram_pentes'      => $pentesCount,

        // Dados de Disco
        'disco_total_gb'  => $discoTotalGB,
        'disco_livre_gb'  => $discoLivreGB,

        'alertas'         => $alertas
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