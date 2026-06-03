<?php
$blogPostSlug = 'testei-o-gpt-5-3-codex-veja-o-que-encontrei';
$blogPostData = [
    'title' => 'Testei o GPT-5.3 Codex. Veja o que encontrei.',
    'excerpt' => 'Eu executei uma tarefa real de programação com o GPT‑5.3 Codex e ele foi claramente melhor que as versões anteriores: menos alucinações, melhor compreensão de contexto e uma qualidade de código impressionante.',
    'featured_image' => '/images/perplexity-ai.webp',
    'published_at' => '2026-01-15 00:00:00',
    'author' => ['full_name' => 'Iago Filgueiras Chiapetta'],
    'content' => <<<'HTML'
<p>
    Eu executei uma tarefa real de programação com o GPT‑5.3 Codex e ele foi claramente melhor que as versões anteriores: menos alucinações, melhor compreensão de contexto e uma qualidade de código impressionante.
</p>

<h3>O Teste</h3>
<p>
    Decidi testar o novo modelo com um projeto real que tinha em mãos: a criação de um sistema de API com autenticação JWT, validação de dados e middleware customizado. Algo que, poucos meses atrás, ainda geraria erros significativos.
</p>

<h3>Resultados Surpreendentes</h3>
<p>
    O GPT-5.3 Codex conseguiu:
</p>
<ul>
    <li>Implementar toda a estrutura da API sem erros sintáticos</li>
    <li>Sugerir padrões de design apropriados</li>
    <li>Gerar testes unitários sem ser solicitado</li>
    <li>Documentar o código automaticamente</li>
</ul>

<h3>O que mudou?</h3>
<p>
    Os principais avanços parecem estar na compreensão contextual e na capacidade de seguir um "estilo" de código. O modelo agora entende melhor quando você quer seguir um padrão específico e mantém consistência ao longo de todo o projeto.
</p>

<h3>Conclusão</h3>
<p>
    Estamos entrando em uma era onde ferramentas de IA para programação não são mais apenas auxiliares. Elas são companheiros verdadeiramente úteis. Mas isso também nos coloca diante de uma questão importante: qual será o papel do desenvolvedor neste novo cenário?
</p>

<div style="border-top: 1px solid rgba(0, 217, 163, 0.1); padding-top: 2rem; margin-top: 3rem;">
    <p>
        <strong>Curiosidade!</strong> Este artigo foi parcialmente escrito com ajuda do GPT-5.3. Sim, é uma meta-análise!
    </p>
</div>
HTML,
    'related' => [
        [
            'title' => 'IA Codifica: Desenvolvedores, Hora de Reimaginarmos Nosso Papel!',
            'excerpt' => 'É 1º de fevereiro de 2026, e a notícia explodiu: a TechGiant X acaba de lançar seu mais novo modelo…',
            'slug' => 'ia-codifica-desenvolvedores-hora-de-reimaginarmos-nosso-papel',
            'featured_image' => '/images/perplexity-ai.webp'
        ],
        [
            'title' => 'Minha primeira experiência como programador CLT. Como está sendo?',
            'excerpt' => 'Recentemente iniciei minha primeira experiência formal como programador com registro em carteira…',
            'slug' => 'minha-primeira-experiencia-como-programador-clt-como-esta-sendo',
            'featured_image' => '/images/windows-10-desenvolvedores.webp'
        ]
    ]
];

include '../../templates/blog-post.php';
