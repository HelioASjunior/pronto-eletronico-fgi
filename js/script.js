/* =========================================================
   Script da tela de Login - PontoEletrônico
   Arquivo: js/script.js
   Ligado ao index.html pela tag <script> no final do <body>.
   A tela de Funcionários também usa este arquivo, só para o olho da senha.
   ========================================================= */

// Pega os elementos da página que vamos usar
const campoSenha = document.getElementById("senha");         // campo de senha
const botaoVerSenha = document.querySelector(".ver-senha");  // botão do olho
const formulario = document.querySelector("form");           // formulário de login
const campoUsuario = document.getElementById("usuario");     // campo de usuário
const listaUsuarios = document.getElementById("usuarios-salvos"); // sugestões (datalist)
const caixaLembrar = document.getElementById("lembrar");     // checkbox "Lembrar de mim"


// ---------- Mostrar / ocultar a senha ----------
// Ao clicar no olho, o tipo do campo alterna entre "password"
// (mostra bolinhas) e "text" (mostra a senha digitada).
botaoVerSenha.addEventListener("click", function () {
    const senhaEscondida = campoSenha.type === "password";

    if (senhaEscondida) {
        campoSenha.type = "text";                              // mostra a senha
        botaoVerSenha.classList.add("visivel");                // CSS troca para o olho riscado
        botaoVerSenha.setAttribute("aria-label", "Ocultar senha");
    } else {
        campoSenha.type = "password";                          // esconde a senha
        botaoVerSenha.classList.remove("visivel");             // CSS volta o olho normal
        botaoVerSenha.setAttribute("aria-label", "Mostrar senha");
    }
});


// ---------- Botão Limpar ----------
// Quando o formulário é limpo, a senha volta a ficar escondida.
formulario.addEventListener("reset", function () {
    campoSenha.type = "password";
    botaoVerSenha.classList.remove("visivel");
    botaoVerSenha.setAttribute("aria-label", "Mostrar senha");
});


// ---------- Autocomplete do usuário ----------
// Os usuários marcados com "Lembrar de mim" ficam guardados no navegador
// (localStorage) e aparecem como sugestão ao digitar no campo de usuário.
// Só o nome de usuário é guardado, nunca a senha.

// Lê a lista guardada. Se ainda não existir nada, começa com uma lista vazia.
function lerUsuariosSalvos() {
    const texto = localStorage.getItem("usuariosSalvos");

    if (texto === null) {
        return [];
    } else {
        return JSON.parse(texto);
    }
}

// Monta as opções do datalist, uma para cada usuário salvo.
function montarSugestoes() {
    const usuarios = lerUsuariosSalvos();
    listaUsuarios.innerHTML = "";                    // limpa as opções antigas

    for (let i = 0; i < usuarios.length; i++) {
        const opcao = document.createElement("option");
        opcao.value = usuarios[i];
        listaUsuarios.appendChild(opcao);
    }

    // O último usuário lembrado já vem preenchido e com a caixa marcada
    if (usuarios.length > 0) {
        campoUsuario.value = usuarios[0];
        caixaLembrar.checked = true;
    }
}

// Ao clicar em Entrar: se "Lembrar de mim" estiver marcado, salva o usuário
// no início da lista (sem repetir e no máximo 5); se não, tira ele da lista.
formulario.addEventListener("submit", function () {
    if (!listaUsuarios) {
        return;                                      // não é a tela de login
    }

    const usuario = campoUsuario.value.trim();
    let usuarios = lerUsuariosSalvos().filter(function (nome) {
        return nome !== usuario;                     // remove se já existia
    });

    if (caixaLembrar.checked) {
        usuarios.unshift(usuario);                   // coloca no início
        usuarios = usuarios.slice(0, 5);             // guarda no máximo 5
    }

    localStorage.setItem("usuariosSalvos", JSON.stringify(usuarios));
});

// Monta as sugestões assim que a página abre.
// Só na tela de login: a tela de Funcionários usa este arquivo apenas
// para o olho da senha e não tem a lista de sugestões nem o "Lembrar de mim".
if (listaUsuarios) {
    montarSugestoes();
    mostrarErroDoLogin();
    irSeJaEstiverLogado();
}


// ---------- Erro do login ----------
// Quando a senha está errada, o PHP/login.php volta para esta página com o
// motivo na URL (index.html?erro=senha). Aqui lemos esse valor e mostramos o aviso.
function mostrarErroDoLogin() {
    const mensagens = {
        senha: "Usuário ou senha incorretos.",
        inativo: "Seu acesso está inativo. Procure o administrador."
    };
    const erro = new URLSearchParams(window.location.search).get("erro");

    if (mensagens[erro]) {
        const aviso = document.getElementById("mensagem");
        aviso.textContent = mensagens[erro];
        aviso.hidden = false;
    }
}


// ---------- Já fez login? ----------
// Pergunta ao PHP (método GET) se já existe alguém logado.
// Se existir, vai direto para a página inicial dessa pessoa.
function irSeJaEstiverLogado() {
    fetch("PHP/usuario.php")
        .then(function (resposta) {
            if (resposta.ok) {
                return resposta.json().then(function (usuario) {
                    window.location.href = usuario.pagina_inicial;
                });
            }
        })
        .catch(function () {
            // Sem servidor (ex.: arquivo aberto direto, sem o XAMPP): não faz nada
        });
}
