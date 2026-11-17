/* =========================================================
   Script da tela de Funcionários - PontoEletrônico
   Arquivo: js/funcionarios.js
   Ligado ao paginas/funcionarios.html, depois do js/comum.js.

   - Lista: busca no PHP/funcionarios_listar.php (GET)
   - Cadastro: envia para o PHP/funcionarios_cadastrar.php (POST)
   - Edição: o lápis preenche o formulário e envia para o
     PHP/funcionarios_editar.php (POST)
   - Desativar: a lixeira envia para o PHP/funcionarios_desativar.php (POST)
   ========================================================= */

const formCadastro = document.getElementById("form-cadastro");
const tabelaFuncionarios = document.getElementById("lista-funcionarios");
const campoBusca = document.getElementById("busca");
const tituloFormulario = document.getElementById("titulo-formulario");
const campoId = document.getElementById("id_funcionario");
const campoSenhaCadastro = document.getElementById("senha");
const botaoLimpar = document.getElementById("botao-limpar");
const botaoSalvar = document.getElementById("botao-salvar");

// A busca usa GET, então o texto está na URL: funcionarios.html?busca=maria
const parametros = new URLSearchParams(window.location.search);
const textoBusca = parametros.get("busca") || "";
campoBusca.value = textoBusca;               // deixa o texto no campo de busca

// Funcionários que vieram do PHP, guardados para o botão Editar
// encontrar os dados de quem foi clicado
let funcionariosCarregados = [];


// ---------- Lista de funcionários ----------
async function carregarFuncionarios() {
    const resposta = await pedirAoPhp("../PHP/funcionarios_listar.php?busca=" + encodeURIComponent(textoBusca));
    funcionariosCarregados = resposta.dados.funcionarios;

    if (funcionariosCarregados.length === 0) {
        const aviso = textoBusca === ""
            ? "Nenhum funcionário cadastrado ainda."
            : "Nenhum funcionário encontrado para \"" + textoBusca + "\".";
        mostrarTabelaVazia(tabelaFuncionarios, 6, aviso);
        return;
    }

    tabelaFuncionarios.innerHTML = "";
    for (const funcionario of funcionariosCarregados) {
        const linha = document.createElement("tr");
        linha.dataset.id = funcionario.id;   // id guardado na linha (data-id)

        linha.appendChild(criarCelula(funcionario.nome));
        linha.appendChild(criarCelula(funcionario.cpf));
        // || "—": se o campo estiver vazio (null), mostra um traço
        linha.appendChild(criarCelula(funcionario.cargo || "—"));
        linha.appendChild(criarCelula(funcionario.departamento || "—"));

        // Etiqueta azul para Administrador e verde para Funcionário
        linha.appendChild(criarCelulaEtiqueta(
            funcionario.acesso,
            funcionario.tipo_acesso === "administrador" ? "tag admin" : "tag"
        ));

        // Botões de editar (lápis) e desativar (lixeira)
        const acoes = document.createElement("td");
        acoes.innerHTML = '<button type="button" class="editar" aria-label="Editar" title="Editar"></button>' +
                          '<button type="button" class="excluir" aria-label="Desativar" title="Desativar"></button>';
        linha.appendChild(acoes);

        tabelaFuncionarios.appendChild(linha);
    }
}


// ---------- Modo edição ----------
// O mesmo formulário serve para cadastrar e para editar.
// Ao clicar no lápis ele é preenchido com os dados do funcionário.
function editarFuncionario(funcionario) {
    campoId.value = funcionario.id;
    formCadastro.nome.value = funcionario.nome;
    formCadastro.sexo.value = funcionario.sexo;
    formCadastro.cpf.value = funcionario.cpf;
    formCadastro.usuario.value = funcionario.usuario;
    formCadastro.cargo.value = funcionario.cargo || "";
    formCadastro.departamento.value = funcionario.departamento || "";
    formCadastro.acesso.value = funcionario.tipo_acesso;

    // Na edição a senha é opcional: em branco, continua a mesma
    campoSenhaCadastro.value = "";
    campoSenhaCadastro.required = false;
    campoSenhaCadastro.placeholder = "Deixe em branco";

    tituloFormulario.textContent = "Editar Funcionário";
    botaoSalvar.textContent = "Salvar alterações";
    botaoLimpar.textContent = "Cancelar";

    // Destaca a linha que está sendo editada
    for (const linha of tabelaFuncionarios.querySelectorAll("tr")) {
        linha.classList.toggle("editando", linha.dataset.id === String(funcionario.id));
    }

    document.getElementById("mensagem").hidden = true;
    formCadastro.scrollIntoView({ behavior: "smooth", block: "center" });
    formCadastro.nome.focus();
}

// Volta o formulário para o modo cadastro
function voltarParaCadastro() {
    campoId.value = "";
    campoSenhaCadastro.required = true;
    campoSenhaCadastro.placeholder = "Mínimo 6 caracteres";

    tituloFormulario.textContent = "Cadastrar Funcionário";
    botaoSalvar.textContent = "Salvar";
    botaoLimpar.textContent = "Limpar";

    for (const linha of tabelaFuncionarios.querySelectorAll("tr.editando")) {
        linha.classList.remove("editando");
    }
}

// ---------- Desativar ----------
// O funcionário não é apagado: fica inativo, some da lista e não entra mais.
// O histórico de ponto dele continua guardado no banco.
async function desativarFuncionario(funcionario) {
    // confirm() abre uma janela com OK e Cancelar
    const confirmou = confirm(
        "Desativar " + funcionario.nome + "?\n\n" +
        "Ele não vai mais conseguir entrar no sistema, mas o histórico de ponto fica guardado."
    );
    if (!confirmou) {
        return;
    }

    // Envia só o id, com POST
    const dados = new FormData();
    dados.append("id_funcionario", funcionario.id);

    const resposta = await pedirAoPhp("../PHP/funcionarios_desativar.php", {
        method: "POST",
        body: dados
    });

    if (resposta.ok) {
        // Se estava editando quem foi desativado, limpa o formulário
        if (campoId.value === String(funcionario.id)) {
            formCadastro.reset();
        }
        mostrarMensagem("sucesso", resposta.dados.mensagem);
        carregarFuncionarios();
    } else {
        mostrarMensagem("erro", resposta.dados.erro);
    }
}


// ---------- Cliques nos botões da tabela ----------
// Os botões são criados depois que a lista chega, então o clique é ouvido
// na tabela inteira e conferimos se foi no lápis ou na lixeira.
tabelaFuncionarios.addEventListener("click", function (evento) {
    const botao = evento.target.closest(".editar, .excluir");
    if (!botao) {
        return;                              // clicou em outro lugar da tabela
    }

    // Encontra o funcionário da linha clicada (pelo data-id da linha)
    const id = Number(botao.closest("tr").dataset.id);
    const funcionario = funcionariosCarregados.find(function (f) {
        return f.id === id;
    });

    if (botao.classList.contains("editar")) {
        editarFuncionario(funcionario);
    } else {
        desativarFuncionario(funcionario);
    }
});

// Botão Limpar / Cancelar: limpa o formulário e sai do modo edição
formCadastro.addEventListener("reset", voltarParaCadastro);


// ---------- Salvar (cadastro ou edição) ----------
// Envia o formulário para o PHP com POST, sem recarregar a página.
// FormData junta todos os campos do formulário (pelo name de cada um).
formCadastro.addEventListener("submit", async function (evento) {
    evento.preventDefault();

    // Com id preenchido é edição; sem id é cadastro novo
    const editando = campoId.value !== "";
    const arquivoPhp = editando ? "funcionarios_editar.php" : "funcionarios_cadastrar.php";

    const resposta = await pedirAoPhp("../PHP/" + arquivoPhp, {
        method: "POST",
        body: new FormData(formCadastro)
    });

    if (resposta.ok) {
        formCadastro.reset();           // limpa e volta para o modo cadastro
        mostrarMensagem("sucesso", resposta.dados.mensagem);
        carregarFuncionarios();         // atualiza a lista
    } else {
        // Em caso de erro o formulário continua preenchido para corrigir
        mostrarMensagem("erro", resposta.dados.erro);
    }
});


// Ao abrir a página: confere se é administrador e depois carrega a lista
carregarUsuario(true).then(carregarFuncionarios);
