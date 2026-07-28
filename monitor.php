// C:\MeuMonitorAgent\monitor.php
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    exit("Erro: config.json nao encontrado.\n");
}

$config = json_decode(file_get_contents($configFile), true);

$payload = [
    'token'           => $config['api_token'] ?? '',
    'hostname'        => $config['hostname_custom'] ?? gethostname(),
    'setor_nome'      => $config['setor_nome'] ?? 'Geral',
    'cpu_modelo'      => getCpuModel(),
    'gpu_modelo'      => getGpuModel(),
    'ram_total_mb'    => $ramTotal,
    'ram_livre_mb'    => $ramLivre,
    'disco_livre_gb'  => $discoLivreGb,
    'alertas'         => $alertas
];