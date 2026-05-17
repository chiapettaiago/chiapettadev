<?php include '../../templates/header.php'; ?>

<main id="primary" class="py-5">
    <!-- Article Header -->
    <section style="border-top: none;">
        <div class="container">
            <div style="max-width: 800px;">
                <h1 style="font-size: 2.5rem; line-height: 1.3; margin-bottom: 1.5rem;">
                    Testei o GPT-5.3 Codex. Veja o que encontrei.
                </h1>
                <div style="display: flex; gap: 2rem; color: var(--text-muted); margin-bottom: 2rem;">
                    <span>📅 15 de Fevereiro de 2026</span>
                    <span>👤 Iago Filgueiras Chiapetta</span>
                </div>
                <img src="/images/perplexity-ai.webp" alt="Testei o GPT-5.3 Codex. Veja o que encontrei." style="width: 100%; aspect-ratio: 16 / 9; object-fit: cover; border-radius: 0.5rem; border: 1px solid rgba(0, 217, 163, 0.12);">
            </div>
        </div>
    </section>

    <!-- Article Content -->
    <section class="py-5">
        <div class="container">
            <div style="max-width: 800px; line-height: 1.8; color: var(--text-muted);">
                <p>
                    Eu executei uma tarefa real de programação com o GPT‑5.3 Codex e ele foi claramente melhor que as versões anteriores: menos alucinações, melhor compreensão de contexto e uma qualidade de código impressionante.
                </p>

                <h3 style="color: var(--accent); margin-top: 2rem; margin-bottom: 1rem;">O Teste</h3>
                <p>
                    Decidi testar o novo modelo com um projeto real que tinha em mãos: a criação de um sistema de API com autenticação JWT, validação de dados e middleware customizado. Algo que, poucos meses atrás, ainda geraria erros significativos.
                </p>

                <h3 style="color: var(--accent); margin-top: 2rem; margin-bottom: 1rem;">Resultados Surpreendentes</h3>
                <p>
                    O GPT-5.3 Codex conseguiu:
                </p>
                <ul style="color: var(--text-muted); margin-left: 2rem;">
                    <li>Implementar toda a estrutura da API sem erros sintáticos</li>
                    <li>Sugerir padrões de design apropriados</li>
                    <li>Gerar testes unitários sem ser solicitado</li>
                    <li>Documentar o código automaticamente</li>
                </ul>

                <h3 style="color: var(--accent); margin-top: 2rem; margin-bottom: 1rem;">O que mudou?</h3>
                <p>
                    Os principais avanços parecem estar na compreensão contextual e na capacidade de seguir um "estilo" de código. O modelo agora entende melhor quando você quer seguir um padrão específico e mantém consistência ao longo de todo o projeto.
                </p>

                <h3 style="color: var(--accent); margin-top: 2rem; margin-bottom: 1rem;">Conclusão</h3>
                <p>
                    Estamos entrando em uma era onde ferramentas de IA para programação não são mais apenas auxiliares. Elas são companheiros verdadeiramente úteis. Mas isso também nos coloca diante de uma questão importante: qual será o papel do desenvolvedor neste novo cenário?
                </p>

                <div style="border-top: 1px solid rgba(0, 217, 163, 0.1); padding-top: 2rem; margin-top: 3rem;">
                    <p>
                        <strong>Curiosidade!</strong> Este artigo foi parcialmente escrito com ajuda do GPT-5.3. Sim, é uma meta-análise!
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php $commentPostSlug = basename(__DIR__); include '../../templates/comments.php'; ?>

    <!-- Related Articles -->
    <section class="py-5">
        <div class="container">
            <h2 style="margin-bottom: 2rem;">Artigos Relacionados</h2>
            <div class="blog-grid">
                <div class="blog-card">
                    <h3>IA Codifica: Desenvolvedores, Hora de Reimaginarmos Nosso Papel!</h3>
                    <p>É 1º de fevereiro de 2026, e a notícia explodiu: a TechGiant X acaba de lançar seu mais novo modelo…</p>
                    <a href="../ia-codifica-desenvolvedores-hora-de-reimaginarmos-nosso-papel/">Continuar lendo →</a>
                </div>
                <div class="blog-card">
                    <h3>Minha primeira experiência como programador CLT. Como está sendo?</h3>
                    <p>Recentemente iniciei minha primeira experiência formal como programador com registro em carteira…</p>
                    <a href="../minha-primeira-experiencia-como-programador-clt-como-esta-sendo/">Continuar lendo →</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../../templates/footer.php'; ?>
