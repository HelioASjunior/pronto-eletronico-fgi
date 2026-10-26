/* =========================================================
   Script da tela de Login - PontoEletrônico
   Arquivo: js/script.js
   Ligado ao index.html pela tag <script> no final do <body>
   ========================================================= */

// Pega os elementos da página que vamos usar
const campoSenha = document.getElementById("senha");         // campo de senha
const botaoVerSenha = document.querySelector(".ver-senha");  // botão do olho
const formulario = document.querySelector("form");           // formulário de login


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
