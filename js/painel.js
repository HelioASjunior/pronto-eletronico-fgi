/* =========================================================
   Script da tela Registrar Ponto (painel do funcionário) - PontoEletrônico
   Arquivo: js/painel.js
   Ligado ao paginas/painel.html, depois do js/comum.js
   ========================================================= */

// Pega os elementos da página que vamos usar
const hora = document.getElementById("hora");                 // relógio
const data = document.getElementById("data");                 // data abaixo do relógio
const dataExtenso = document.getElementById("data-extenso");  // data do cabeçalho
const dataCurta = document.getElementById("data-curta");      // data do card "Hoje"


// ---------- Relógio ----------
// Escreve a hora e as datas atuais na página.
// toLocaleTimeString / toLocaleDateString formatam no padrão do Brasil (pt-BR).
function atualizarRelogio() {
    const agora = new Date();

    hora.textContent = agora.toLocaleTimeString("pt-BR");                    // 11:24:36

    const diaSemana = agora.toLocaleDateString("pt-BR", { weekday: "long" }); // segunda-feira
    const diaMesAno = agora.toLocaleDateString("pt-BR");                      // 29/09/2025

    // Primeira letra maiúscula: "Segunda-feira, 29/09/2025"
    data.textContent = diaSemana[0].toUpperCase() + diaSemana.slice(1) + ", " + diaMesAno;
    dataCurta.textContent = diaMesAno;
    dataExtenso.textContent = "Hoje é " + agora.toLocaleDateString("pt-BR", { dateStyle: "full" });
}

// Mostra a hora assim que a página abre e depois atualiza a cada 1 segundo (1000 ms)
atualizarRelogio();
setInterval(atualizarRelogio, 1000);


// ---------- Pontos do banco ----------
// Os dados vêm do PHP/ponto_hoje.php (GET) e o registro vai para o
// PHP/ponto_registrar.php (POST). As funções pedirAoPhp, carregarUsuario,
// mostrarMensagem e criarCelula estão no js/comum.js.

const saudacao = document.getElementById("saudacao");
const listaHoje = document.getElementById("marcacoes-hoje");
const tabelaUltimos = document.getElementById("ultimos-registros");
const proximoTipo = document.getElementById("proximo-tipo");
const formPonto = document.getElementById("form-ponto");
const botaoRegistrar = formPonto.querySelector("button");

// Como cada tipo aparece na tela
const nomeDoTipo = { entrada: "Entrada", saida: "Saída" };

// Busca no PHP as marcações de hoje e os últimos registros e mostra na tela
async function carregarPontos() {
    const resposta = await pedirAoPhp("../PHP/ponto_hoje.php");
    const pontos = resposta.dados;

    proximoTipo.textContent = nomeDoTipo[pontos.proximo_tipo];

    // Card "Hoje": sempre pelo menos 4 linhas (Entrada, Saída, Entrada, Saída).
    // As que ainda não foram registradas aparecem como --:--
    listaHoje.innerHTML = "";
    const totalLinhas = Math.max(4, pontos.hoje.length);

    for (let i = 0; i < totalLinhas; i++) {
        const tipo = i % 2 === 0 ? "entrada" : "saida";
        const horario = document.createElement("time");
        horario.textContent = pontos.hoje[i] ? pontos.hoje[i].hora : "--:--";

        const item = document.createElement("li");
        item.className = tipo;                         // cor do ícone (CSS)
        item.textContent = nomeDoTipo[tipo] + " ";
        item.appendChild(horario);
        listaHoje.appendChild(item);
    }

    // Tabela "Últimos registros"
    if (pontos.ultimos.length === 0) {
        mostrarTabelaVazia(tabelaUltimos, 4, "Nenhum registro de ponto ainda.");
        return;
    }

    tabelaUltimos.innerHTML = "";
    for (const registro of pontos.ultimos) {
        const linha = document.createElement("tr");
        linha.appendChild(criarCelula(registro.data));
        linha.appendChild(criarCelula(registro.hora));
        // Etiqueta verde para Entrada e vermelha para Saída
        linha.appendChild(criarCelulaEtiqueta(
            nomeDoTipo[registro.tipo],
            registro.tipo === "saida" ? "tag saida" : "tag"
        ));
        linha.appendChild(criarCelula(registro.observacao || "—"));
        tabelaUltimos.appendChild(linha);
    }
}

// Botão "Registrar Ponto": envia para o PHP com POST sem recarregar a página
formPonto.addEventListener("submit", async function (evento) {
    evento.preventDefault();            // impede o envio normal do formulário
    botaoRegistrar.disabled = true;     // evita clique duplo enquanto espera

    const resposta = await pedirAoPhp("../PHP/ponto_registrar.php", { method: "POST" });

    if (resposta.ok) {
        mostrarMensagem("sucesso", resposta.dados.mensagem);
    } else {
        mostrarMensagem("erro", resposta.dados.erro);
    }

    botaoRegistrar.disabled = false;
    carregarPontos();                   // atualiza o "Hoje" e os "Últimos registros"
});

// Ao abrir a página: confere o login e depois carrega os pontos
carregarUsuario(false).then(function (usuario) {
    saudacao.textContent = "Olá, " + usuario.primeiro_nome + "!";
    carregarPontos();
});
