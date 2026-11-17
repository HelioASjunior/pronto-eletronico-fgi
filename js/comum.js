/* =========================================================
   Funções comuns das páginas internas - PontoEletrônico
   Arquivo: js/comum.js
   Ligado ao painel.html, espelho.html e funcionarios.html,
   sempre ANTES do script da própria página.

   Como as páginas são HTML puro, é o JavaScript que conversa com o PHP:
   o fetch() faz um pedido HTTP para um arquivo da pasta PHP e recebe
   a resposta em JSON (texto com os dados, ex.: {"nome": "Maria"}).
   ========================================================= */


// ---------- Pedido para o PHP ----------
// url: arquivo PHP que vai responder (ex.: "../PHP/ponto_hoje.php")
// opcoes: o método HTTP e os dados. Sem opcoes o fetch usa GET.
//         Para POST: { method: "POST", body: dados }
// Devolve { ok, dados }: ok = true se deu certo, dados = o JSON da resposta.
async function pedirAoPhp(url, opcoes) {
    // await espera a resposta do servidor chegar antes de continuar
    const resposta = await fetch(url, opcoes);

    // 401 = ninguém logado (ou a sessão acabou): volta para o login
    if (resposta.status === 401) {
        window.location.href = "../index.html";
        throw new Error("Sem login");      // para o resto do código
    }

    const dados = await resposta.json();   // transforma o texto JSON em objeto
    return { ok: resposta.ok, dados: dados };
}


// ---------- Usuário logado ----------
// Toda página chama ao abrir. Pergunta ao PHP quem está logado (GET),
// mostra o menu do administrador e preenche o nome no cabeçalho.
// somenteAdministrador: true nas páginas que o funcionário não pode ver.
async function carregarUsuario(somenteAdministrador) {
    const resposta = await pedirAoPhp("../PHP/usuario.php");
    const usuario = resposta.dados;
    const ehAdministrador = usuario.tipo_acesso === "administrador";

    // Funcionário tentando abrir página de administrador: vai para o painel
    if (somenteAdministrador && !ehAdministrador) {
        window.location.href = "painel.html";
        throw new Error("Sem permissão");
    }

    // A classe "admin" no <body> faz o CSS mostrar os links com "so-admin"
    if (ehAdministrador) {
        document.body.classList.add("admin");
    }

    // Bloco do usuário no cabeçalho (se a página tiver)
    const bloco = document.querySelector(".usuario");
    if (bloco) {
        bloco.dataset.inicial = usuario.inicial;              // letra do círculo verde
        bloco.querySelector("strong").textContent = usuario.nome;
        bloco.querySelector("span").textContent = usuario.acesso;
    }

    return usuario;
}


// ---------- Mensagem de aviso ----------
// tipo: "sucesso" (verde) ou "erro" (vermelho)
function mostrarMensagem(tipo, texto) {
    const mensagem = document.getElementById("mensagem");
    mensagem.className = "mensagem " + tipo;
    mensagem.textContent = texto;
    mensagem.hidden = false;
}


// ---------- Montagem das tabelas ----------
// Usamos textContent (e não innerHTML) para colocar os textos que vieram
// do banco. Assim, se alguém cadastrar um nome com "<script>", ele aparece
// como texto comum e não é executado.

// Cria uma célula <td> com um texto
function criarCelula(texto) {
    const celula = document.createElement("td");
    celula.textContent = texto;
    return celula;
}

// Cria uma célula com uma etiqueta arredondada: <td><span class="tag">Texto</span></td>
function criarCelulaEtiqueta(texto, classe) {
    const etiqueta = document.createElement("span");
    etiqueta.className = classe;
    etiqueta.textContent = texto;

    const celula = document.createElement("td");
    celula.appendChild(etiqueta);
    return celula;
}

// Deixa a tabela só com uma linha de aviso (ex.: "Nenhum registro ainda.")
function mostrarTabelaVazia(corpoTabela, colunas, texto) {
    const celula = criarCelula(texto);
    celula.colSpan = colunas;              // ocupa todas as colunas
    celula.className = "vazio";

    const linha = document.createElement("tr");
    linha.appendChild(celula);

    corpoTabela.innerHTML = "";
    corpoTabela.appendChild(linha);
}
