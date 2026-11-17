/* =========================================================
   Script da tela Espelho de Ponto - PontoEletrônico
   Arquivo: js/espelho.js
   Ligado ao paginas/espelho.html, depois do js/comum.js.

   Busca no PHP/ponto_espelho.php (GET) o histórico do mês e monta a tabela.
   ========================================================= */

const campoMes = document.getElementById("mes");
const tabelaDias = document.getElementById("dias");
const totalMes = document.getElementById("total-mes");

// O filtro usa GET, então o mês escolhido está na URL: espelho.html?mes=2026-11
// URLSearchParams lê esse valor. Sem mês na URL, fica "" e o PHP usa o mês atual.
const parametros = new URLSearchParams(window.location.search);
const mesEscolhido = parametros.get("mes") || "";

async function carregarEspelho() {
    // encodeURIComponent deixa o texto seguro para ir na URL
    const resposta = await pedirAoPhp("../PHP/ponto_espelho.php?mes=" + encodeURIComponent(mesEscolhido));
    const espelho = resposta.dados;

    campoMes.value = espelho.mes;            // mostra no filtro o mês que está na tela
    totalMes.textContent = espelho.total_mes;

    if (espelho.dias.length === 0) {
        mostrarTabelaVazia(tabelaDias, 7, "Nenhum registro de ponto neste mês.");
        return;
    }

    // Uma linha por dia: data, 4 horários, total e observação
    tabelaDias.innerHTML = "";
    for (const dia of espelho.dias) {
        const linha = document.createElement("tr");
        linha.appendChild(criarCelula(dia.data));

        // Entrada, Saída, Entrada, Saída (o que não foi registrado fica --:--)
        for (let i = 0; i < 4; i++) {
            linha.appendChild(criarCelula(dia.marcacoes[i] || "--:--"));
        }

        // Total do dia em negrito
        const total = document.createElement("strong");
        total.textContent = dia.total;
        const celulaTotal = document.createElement("td");
        celulaTotal.appendChild(total);
        linha.appendChild(celulaTotal);

        linha.appendChild(criarCelula(dia.observacao || "—"));
        tabelaDias.appendChild(linha);
    }
}

// Ao abrir a página: confere o login e depois carrega o histórico
carregarUsuario(false).then(carregarEspelho);
