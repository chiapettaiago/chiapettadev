<?php include '../../templates/header.php'; ?>

<main id="primary" class="py-5">
    <!-- Article Header -->
    <section style="border-top: none;">
        <div class="container">
            <div style="max-width: 800px;">
                <h1 style="font-size: 2.5rem; line-height: 1.3; margin-bottom: 1.5rem;">
                    Minha primeira experiência como programador CLT. Como está sendo?
                </h1>
                <div style="display: flex; gap: 2rem; color: var(--text-muted); margin-bottom: 2rem;">
                    <span>📅 1º de Dezembro de 2025</span>
                    <span>👤 Iago Filgueiras Chiapetta</span>
                </div>
                <img src="/images/windows-10-desenvolvedores.webp" alt="Minha primeira experiência como programador CLT. Como está sendo?" style="width: 100%; aspect-ratio: 16 / 9; object-fit: cover; border-radius: 0.5rem; border: 1px solid rgba(0, 217, 163, 0.12);">
            </div>
        </div>
    </section>

    <!-- Article Content -->
    <section class="py-5">
        <div class="container">
            <div style="max-width: 800px; line-height: 1.8; color: var(--text-muted);">
                <p>
                    Recentemente iniciei minha primeira experiência formal como programador com registro em carteira. No dia 01 de dezembro de 2025, comecei uma jornada que há muito tempo desejava. Deixei de lado o trabalho freelancer e os projetos por demanda para me dedicar a uma empresa.
                </p>

                <h3 style="color: var(--accent); margin-top: 2rem; margin-bottom: 1rem;">Os primeiros dias</h3>
                <p>
                    Os primeiros dias foram de adaptação. Aprender sobre os processos internos, conhecer o time, entender a cultura da empresa. Tudo é novo, mas ao mesmo tempo familiar. As tecnologias que uso não são tão diferentes do que já trabalhei.
                </p>

                <h3 style="color: var(--accent); margin-top: 2rem; margin-bottom: 1rem;">Desafios</h3>
                <p>
                    O maior desafio tem sido lidar com a responsabilidade de estar inserido em um contexto corporativo. Não é apenas sobre código, é sobre comunicação, prazos e colaboração. Mas estou aprendendo muito com isso.
                </p>

                <h3 style="color: var(--accent); margin-top: 2rem; margin-bottom: 1rem;">Conclusão</h3>
                <p>
                    Estou extremamente grato por essa oportunidade. O caminho apenas começou e tenho certeza de que há muito mais a aprender. Vou continuar evoluindo e documentando essa jornada.
                </p>

                <div style="border-top: 1px solid rgba(0, 217, 163, 0.1); padding-top: 2rem; margin-top: 3rem;">
                    <p>
                        <strong>Gostou deste artigo?</strong> Compartilhe suas experiências comigo pelo 
                        <a href="https://wa.me/5521972940130?text=Oi Iago, li seu artigo sobre experiência CLT..." style="color: var(--accent); text-decoration: none;">WhatsApp</a> 
                        ou 
                        <a href="mailto:iagochiapetta@gmail.com" style="color: var(--accent); text-decoration: none;">Email</a>.
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
                    <h3>Testei o GPT-5.3 Codex. Veja o que encontrei.</h3>
                    <p>Eu executei uma tarefa real de programação com o GPT‑5.3 Codex e ele foi claramente melhor que as versões anteriores…</p>
                    <a href="../testei-o-gpt-5-3-codex-veja-o-que-encontrei/">Continuar lendo →</a>
                </div>
                <div class="blog-card">
                    <h3>IA Codifica: Desenvolvedores, Hora de Reimaginarmos Nosso Papel!</h3>
                    <p>É 1º de fevereiro de 2026, e a notícia explodiu: a TechGiant X acaba de lançar seu mais novo modelo…</p>
                    <a href="../ia-codifica-desenvolvedores-hora-de-reimaginarmos-nosso-papel/">Continuar lendo →</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../../templates/footer.php'; ?>
