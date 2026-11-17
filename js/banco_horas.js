/* =========================================================
   Script da tela Banco de Horas - PontoEletrônico
   Arquivo: js/banco_horas.js
   Ligado ao paginas/banco_horas.html, depois do js/comum.js.

   Busca no PHP/banco_horas.php (GET) o saldo de cada funcionário
   no mês e preenche os cards de resumo e a tabela.
   Página só do administrador.
   ========================================================= */

const campoBusca = document.getElementById("busca");
const campoMes = document.getElementById("mes");
const tabelaSaldos = document.getElementById("lista-saldos");
const textoAte = document.getElementById("contado-ate");

// O filtro usa GET, então os valores estão na URL: banco_horas.html?mes=2026-11&busca=maria
const parametros = new URLSearchParams(window.location.search);
const textoBusca = parametros.get("busca") || "";
const mesEscolhido = parametros.get("mes") || "";
campoBusca.value = textoBusca;


// Classe da cor do saldo: verde se sobra hora, vermelho se falta
function classeDoSaldo(segundos) {
    if (segundos > 0) {
        return "saldo positivo";
    }
    if (segundos < 0) {
        return "saldo negativo";
    }
    return "saldo";
}


async function carregarBancoDeHoras() {
    const url = "../PHP/banco_horas.php?mes=" + encodeURIComponent(mesEscolhido) +
                "&busca=" + encodeURIComponent(textoBusca);
    const resposta = await pedirAoPhp(url);
    const banco = resposta.dados;

    campoMes.value = banco.mes;
    textoAte.textContent = banco.ate || "— (nenhum dia deste mês terminou ainda)";

    // Cards de resumo
    document.getElementById("total-positivo").textContent = banco.total_positivo;
    document.getElementById("total-negativo").textContent = banco.total_negativo;
    const saldoGeral = document.getElementById("saldo-geral");
    saldoGeral.textContent = banco.saldo_geral;
    saldoGeral.className = classeDoSaldo(banco.saldo_geral_segundos);

    // Tabela: uma linha por funcionário
    if (banco.funcionarios.length === 0) {
        mostrarTabelaVazia(tabelaSaldos, 6, "Nenhum funcionário encontrado.");
        return;
    }

    tabelaSaldos.innerHTML = "";
    for (const funcionario of banco.funcionarios) {
        const linha = document.createElement("tr");
        linha.appendChild(criarCelula(funcionario.nome));
        linha.appendChild(criarCelula(funcionario.departamento || "—"));
        linha.appendChild(criarCelula(funcionario.dias_trabalhados + " de " + funcionario.dias_previstos));
        linha.appendChild(criarCelula(funcionario.horas_previstas));
        linha.appendChild(criarCelula(funcionario.horas_trabalhadas));

        // Saldo colorido (verde ou vermelho)
        const saldo = criarCelula(funcionario.saldo);
        saldo.className = classeDoSaldo(funcionario.saldo_segundos);
        linha.appendChild(saldo);

        tabelaSaldos.appendChild(linha);
    }
}

// Ao abrir a página: confere se é administrador (true) e depois carrega os saldos
carregarUsuario(true).then(carregarBancoDeHoras);
