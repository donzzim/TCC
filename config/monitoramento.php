<?php

return [
    'ativo' => (bool) env('MONITORAMENTO_ATIVO', false),
    'devs' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('MONITORAMENTO_DEVS', ''))
    ))),
    'limiar_lenta_ms' => 1000,
    'amostragem' => 0.01,
    'limiar_n_mais_um' => 20,
    'online_web_min' => 5,
    'online_app_min' => 15,
    'retencao_bruta_horas' => 48,
    'retencao_hora_dias' => 30,
    'retencao_snapshots_dias' => 14,
    'buffer_dir' => storage_path('app/monitoramento/buffer'),
    'faixas_ms' => App\Monitoramento\Agregacao\Histograma::FAIXAS,
];
