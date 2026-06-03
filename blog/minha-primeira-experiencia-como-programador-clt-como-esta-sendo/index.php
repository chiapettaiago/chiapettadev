<?php
$blogPostSlug = 'minha-primeira-experiencia-como-programador-clt-como-esta-sendo';
$blogPostData = [
    'title' => 'Minha primeira experiência como programador CLT. Como está sendo?',
    'excerpt' => 'Recentemente iniciei minha primeira experiência formal como programador com registro em carteira. No dia 01 de dezembro de 2025, comecei uma jornada que há muito tempo desejava.',
    'featured_image' => '/images/windows-10-desenvolvedores.webp',
    'published_at' => '2025-12-01 00:00:00',
    'author' => ['full_name' => 'Iago Filgueiras Chiapetta'],
    'content' => <<<'HTML'
<p>
    Recentemente iniciei minha primeira experiência formal como programador com registro em carteira. No dia 01 de dezembro de 2025, comecei uma jornada que há muito tempo desejava. Deixei de lado o trabalho freelancer e os projetos por demanda para me dedicar a uma empresa.
</p>

<h3>Os primeiros dias</h3>
<p>
    Os primeiros dias foram de adaptação. Aprender sobre os processos internos, conhecer o time, entender a cultura da empresa. Tudo é novo, mas ao mesmo tempo familiar. As tecnologias que uso não são tão diferentes do que já trabalhei.
</p>

<h3>Desafios</h3>
<p>
    O maior desafio tem sido lidar com a responsabilidade de estar inserido em um contexto corporativo. Não é apenas sobre código, é sobre comunicação, prazos e colaboração. Mas estou aprendendo muito com isso.
</p>

<h3>Conclusão</h3>
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
HTML,
    'related' => [
        [
            'title' => 'Testei o GPT-5.3 Codex. Veja o que encontrei.',
            'excerpt' => 'Eu executei uma tarefa real de programação com o GPT‑5.3 Codex e ele foi claramente melhor que as versões anteriores…',
            'slug' => 'testei-o-gpt-5-3-codex-veja-o-que-encontrei',
            'featured_image' => '/images/perplexity-ai.webp'
        ],
        [
            'title' => 'IA Codifica: Desenvolvedores, Hora de Reimaginarmos Nosso Papel!',
            'excerpt' => 'É 1º de fevereiro de 2026, e a notícia explodiu: a TechGiant X acaba de lançar seu mais novo modelo…',
            'slug' => 'ia-codifica-desenvolvedores-hora-de-reimaginarmos-nosso-papel',
            'featured_image' => '/images/perplexity-ai.webp'
        ]
    ]
];

include '../../templates/blog-post.php';
