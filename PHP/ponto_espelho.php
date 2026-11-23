<?php
// Espelho de ponto - PontoEletrônico
// Arquivo: PHP/ponto_espelho.php
// Método: GET. Usado pelo paginas/espelho.html.
// O mês vai na URL: ponto_espelho.php?mes=2026-11 (sem mês = mês atual).

// 1. Carrega a conexão e a sessão.
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/sessao.php';

// 2. Precisa estar logado, e só com GET.
exigirMetodo('GET');
exigirLogin();

// 3. Recebe o mês. Se vier vazio ou errado, usa o mês atual.
$mes = $_GET['mes'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
    $mes = date('Y-m');
}

// 4. Período: do dia 1 do mês até o dia 1 do mês seguinte.
$inicio = $mes . '-01 00:00:00';
$fim = date('Y-m-d 00:00:00', strtotime($inicio . ' +1 month'));

// 5. Prepara e executa a busca das marcações do mês.
$sql = 'SELECT data_hora, observacao
        FROM registros_ponto
        WHERE id_funcionario = ? AND data_hora >= ? AND data_hora < ?
        ORDER BY data_hora';
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id_funcionario'], $inicio, $fim]);

// 6. Separa as marcações por dia:
//    $porDia['2026-11-17'] = [marcação 1, marcação 2, ...]
$porDia = [];
foreach ($stmt->fetchAll() as $registro) {
    $dia = date('Y-m-d', strtotime($registro['data_hora']));
    $porDia[$dia][] = $registro;
}

// 7. Monta cada dia: horários, total trabalhado e observação.
$dias = [];
$segundosNoMes = 0;

foreach ($porDia as $dia => $marcacoes) {
    // Total do dia = soma de cada par (saída - entrada).
    // Se a última entrada ainda não tem saída, ela não conta.
    $segundosNoDia = 0;
    for ($i = 0; $i + 1 < count($marcacoes); $i += 2) {
        $segundosNoDia += strtotime($marcacoes[$i + 1]['data_hora']) - strtotime($marcacoes[$i]['data_hora']);
    }
    $segundosNoMes += $segundosNoDia;

    $horarios = [];
    $observacoes = [];
    foreach ($marcacoes as $marcacao) {
        $horarios[] = date('H:i', strtotime($marcacao['data_hora']));
        if ($marcacao['observacao']) {
            $observacoes[] = $marcacao['observacao'];
        }
    }

    // Avisos de marcação a mais ou faltando
    if (count($marcacoes) > 4) {
        $observacoes[] = (count($marcacoes) - 4) . ' marcação(ões) a mais no dia';
    }
    if (count($marcacoes) % 2 === 1) {
        if ($dia === date('Y-m-d')) {
            $observacoes[] = 'Em andamento';
        } else {
            $observacoes[] = 'Falta registrar a saída';
        }
    }

    $dias[] = [
        'data' => date('d/m/Y', strtotime($dia)),
        'marcacoes' => $horarios,
        'total' => formatarHoras($segundosNoDia),
        'observacao' => implode('. ', $observacoes),
    ];
}

// 8. Responde para o JavaScript.
responder([
    'mes' => $mes,
    'total_mes' => formatarHoras($segundosNoMes),
    'dias' => $dias,
]);
