# ChiapettaDev Neverland

Site pessoal, blog e CMS sob medida em PHP, com painel administrativo, autenticação de leitores, comentários, métricas simples, gerenciamento de conteúdo, slides e backups.

O projeto foi pensado para ser simples de hospedar em servidores PHP tradicionais, sem build step obrigatório, sem framework pesado e com banco SQLite local por padrão.

## Visão Geral

**ChiapettaDev Neverland** reúne três partes principais:

- **Site público:** home, blog, páginas estáticas/dinâmicas, área de leitor, comentários e visualizador de slides.
- **CMS administrativo:** painel para gerenciar posts, páginas, imagens, usuários, itens do site, apresentações e backups.
- **Camada de dados local:** SQLite com inicialização automática, tabelas versionadas no código e módulos PHP dedicados por domínio.

## Principais Recursos

- Blog com posts publicados ou em rascunho.
- Páginas gerenciáveis pelo painel.
- Autenticação de administradores, autores, editores e leitores.
- Cadastro público de leitores.
- Comentários em posts para usuários autenticados.
- Captcha matemático em login e cadastro para reduzir automação básica.
- Upload e catalogação de imagens.
- Gerenciamento de itens visuais do site, como navegação, projetos e destaques.
- Criação de apresentações em `/slides/{slug}/`.
- Métricas simples de acesso às páginas públicas.
- Backups locais em ZIP.
- Opção de envio de backups para Google Drive via Service Account.
- Restauração de backups pelo painel.
- Banco SQLite auto-inicializável.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP |
| Banco | SQLite via PDO |
| Frontend | HTML, CSS, JavaScript e Bootstrap CDN |
| Editor de conteúdo | TinyMCE em telas administrativas |
| Autenticação | Sessões PHP + bcrypt |
| Backups | ZipArchive |
| Deploy | Hospedagem PHP tradicional |

## Estrutura do Projeto

```text
.
├── admin/
│   ├── assets/              # CSS do painel
│   ├── modules/             # Regras de domínio do CMS
│   ├── pages/               # Telas administrativas
│   ├── partials/            # Partials do painel
│   ├── dashboard.php        # Dashboard administrativo
│   ├── index.php            # Entrada do admin
│   ├── login.php            # Login do CMS
│   └── logout.php           # Logout do CMS
├── backups/                 # Backups ZIP gerados pelo painel
├── blog/                    # Rotas públicas de posts
├── db/
│   ├── cms.db               # Banco SQLite
│   ├── config.php           # Configuração e wrapper PDO
│   └── init.php             # Criação de tabelas e usuário inicial
├── functions/               # Funções legadas/auxiliares
├── images/                  # Imagens públicas
├── js/                      # Scripts públicos
├── slides/                  # Rotas públicas de apresentações
├── sqls/                    # SQLs auxiliares
├── templates/               # Layout público, comentários e viewer de slides
├── index.php                # Home pública
├── login.php                # Login/cadastro de leitores
├── logout.php               # Logout público
├── CMS_README.md            # Documentação operacional antiga do CMS
└── README.md                # Este arquivo
```

## Módulos do CMS

| Arquivo | Responsabilidade |
| --- | --- |
| `admin/modules/Auth.php` | Login, sessão, permissões, cadastro de leitores e usuários. |
| `admin/modules/Captcha.php` | Captcha simples baseado em sessão. |
| `admin/modules/Post.php` | CRUD de posts, slugs, publicação e integração com blog. |
| `admin/modules/Page.php` | CRUD de páginas e hierarquia. |
| `admin/modules/Image.php` | Upload, validação e metadados de imagens. |
| `admin/modules/Comment.php` | Comentários públicos nos posts. |
| `admin/modules/SiteItem.php` | Itens editáveis do site público. |
| `admin/modules/SiteAccess.php` | Registro e consulta de métricas simples. |
| `admin/modules/SlideDeck.php` | Criação de decks e páginas públicas de slides. |
| `admin/modules/Backup.php` | Backup, download, upload, Google Drive e restauração. |
| `admin/modules/ExistingContentImporter.php` | Importação/sincronização de conteúdo existente. |

## Requisitos

- PHP 8.0 ou superior recomendado.
- Extensão `pdo_sqlite`.
- Extensão `sqlite3`.
- Extensão `zip`, para backups.
- Extensão `gd` ou suporte de imagem equivalente, caso o ambiente utilize processamento de imagens.
- Servidor web com suporte a PHP, como Nginx + PHP-FPM ou Apache.
- Permissão de escrita para:
  - `db/`
  - `backups/`
  - `images/`
  - `admin/uploads/`
  - `blog/`, se o CMS gerar páginas públicas de posts.
  - `slides/`, se o CMS gerar páginas públicas de slides.

## Instalação Local

Clone ou copie o projeto para o diretório desejado:

```bash
git clone <url-do-repositorio>
cd neverland.chiapetta.dev
```

Inicie um servidor PHP local:

```bash
php -S localhost:8000
```

Acesse:

```text
http://localhost:8000
http://localhost:8000/admin/
```

Na primeira execução, o arquivo `db/config.php` verifica se `db/cms.db` existe. Se não existir, `db/init.php` cria as tabelas e o usuário administrador inicial.

## Credenciais Iniciais

O inicializador cria um usuário administrativo padrão:

```text
Usuário: admin
Senha: admin123
```

Depois do primeiro login, altere a senha em:

```text
Admin > Configurações > Alterar Senha
```

Também é recomendado trocar o e-mail padrão do administrador diretamente no painel ou no banco.

## Configuração

As configurações principais ficam em `db/config.php`:

```php
define('DB_PATH', __DIR__ . '/cms.db');
define('DB_TYPE', 'sqlite');
define('SESSION_NAME', 'cms_admin');
define('SESSION_TIMEOUT', 3600);
define('UPLOADS_PATH', __DIR__ . '/../admin/uploads/');
define('PUBLIC_IMAGES_PATH', __DIR__ . '/../images/');
```

Por padrão, o projeto usa SQLite. Há constantes de MySQL preparadas para migração futura, mas a aplicação atual está orientada ao uso de SQLite.

## Rotas Principais

| Rota | Descrição |
| --- | --- |
| `/` | Home pública. |
| `/blog/` | Listagem pública do blog. |
| `/blog/{slug}/` | Post público. |
| `/slides/` | Área pública de slides. |
| `/slides/{slug}/` | Apresentação pública. |
| `/login.php` | Login e cadastro de leitores. |
| `/logout.php` | Logout público. |
| `/admin/` | Entrada do painel administrativo. |
| `/admin/login.php` | Login administrativo. |
| `/admin/dashboard.php` | Dashboard do CMS. |

## Permissões e Papéis

O sistema usa papéis com níveis progressivos:

| Papel | Nível | Uso esperado |
| --- | ---: | --- |
| `reader` | 1 | Leitores autenticados, comentários e conta pública. |
| `author` | 2 | Autores com acesso inicial ao CMS. |
| `editor` | 3 | Gestão editorial. |
| `admin` | 4 | Acesso completo ao painel. |

A checagem é feita por `Auth::hasPermission($role)`.

## Banco de Dados

O banco principal fica em:

```text
db/cms.db
```

Tabelas criadas pelo inicializador:

- `users`
- `posts`
- `pages`
- `images`
- `tags`
- `post_tags`
- `categories`
- `post_categories`
- `settings`
- `site_items`
- `site_accesses`
- `comments`
- `slide_decks`
- `slide_items`
- `backup_runs`

Índices importantes são criados para slugs, status, autores, métricas, comentários, slides e backups.

## Fluxo de Conteúdo

1. Um administrador acessa `/admin/`.
2. Cria ou edita posts, páginas, imagens, slides ou itens do site.
3. Conteúdos publicados ficam disponíveis nas rotas públicas.
4. Leitores podem criar conta em `/login.php`.
5. Leitores autenticados podem comentar em posts.
6. O dashboard acompanha métricas e atalhos administrativos.

## Segurança

O projeto já inclui algumas proteções importantes:

- Hash de senhas com `password_hash` usando bcrypt.
- Verificação de senha com `password_verify`.
- Sessões com timeout.
- Controle de acesso por papel.
- Redirecionamento de leitores que tentam acessar o CMS.
- Captcha em login/cadastro público e login administrativo.
- Uso de consultas preparadas em pontos centrais da camada de banco.
- Validação de tipo e tamanho em uploads.
- Hash de IP para métricas, evitando armazenamento direto do IP bruto.
- `.htaccess` em `backups/` para reduzir exposição dos arquivos de backup.

Recomendações para produção:

- Trocar imediatamente a senha `admin123`.
- Servir o site apenas via HTTPS.
- Restringir listagem de diretórios no servidor web.
- Conferir permissões de escrita somente onde necessário.
- Manter `db/cms.db` fora de acesso público direto.
- Manter backups fora da raiz pública quando possível.
- Revisar credenciais e JSONs de Service Account antes de compartilhar o projeto.

## Backups

O módulo de backups gera arquivos ZIP em `backups/`.

O pacote inclui:

- `db/cms.db`
- `images/`
- `admin/uploads/`
- `blog/`
- `slides/`
- `templates/`
- `js/`
- `index.php`
- `CMS_README.md`
- `backup-manifest.json`

Também há suporte para envio ao Google Drive quando as configurações de Service Account e pasta estão preenchidas no painel.

## Restauração

A restauração pode ser feita pelo painel administrativo em `Backups`.

Antes de restaurar, o sistema tenta criar um backup de segurança do estado atual. Isso reduz risco operacional caso o ZIP enviado ou selecionado esteja incorreto.

Depois de restaurar, pode ser necessário fazer login novamente, especialmente se o banco restaurado tiver sessões ou usuários diferentes.

## Desenvolvimento

Este projeto não depende de Node, Composer ou bundlers para funcionar. A maior parte da lógica vive em arquivos PHP diretos.

Comandos úteis:

```bash
php -l login.php
php -l admin/login.php
php -l admin/modules/Auth.php
php -l admin/modules/Backup.php
```

Para validar vários arquivos manualmente:

```bash
find . -name "*.php" -not -path "./backups/*" -print
```

Em seguida, rode `php -l` nos arquivos alterados.

## Convenções do Projeto

- Preferir módulos em `admin/modules/` para regras de negócio.
- Manter páginas administrativas em `admin/pages/`.
- Manter templates públicos em `templates/`.
- Usar slugs amigáveis para posts, páginas e slides.
- Evitar dependências externas quando PHP nativo resolver bem.
- Preservar compatibilidade com hospedagens PHP comuns.
- Centralizar acesso ao banco pela classe `Database`.

## Deploy

Um deploy típico envolve copiar o projeto para a raiz do domínio e garantir permissões adequadas:

```bash
chmod 755 db backups images admin/uploads blog slides
```

Em servidores com PHP-FPM, confirme que o usuário do PHP consegue escrever nos diretórios acima.

Se estiver usando Nginx ou Apache, configure o document root para a raiz do projeto:

```text
/www/wwwroot/neverland.chiapetta.dev
```

## Manutenção do Servidor

O arquivo `atualizar` é um script simples de manutenção do sistema operacional:

```bash
./atualizar
```

Ele executa atualização de repositórios e pacotes via `apt`. Use com cuidado em produção, preferencialmente em janela de manutenção.

## Solução de Problemas

### O banco não inicializa

Verifique se `db/` existe e se o usuário do PHP tem permissão de escrita.

### O login não funciona

Confirme usuário, senha e status da conta na tabela `users`. Se estiver no admin, a conta precisa ter papel `author`, `editor` ou `admin`.

### Upload de imagem falha

Verifique permissões em `admin/uploads/` e `images/`, limite de upload no `php.ini` e extensões de imagem disponíveis.

### Backup não é criado

Confirme se a extensão `zip` está ativa e se `backups/` permite escrita pelo PHP.

### Slides não aparecem

Confirme se a apresentação está publicada e se o diretório `slides/` tem permissão de escrita para gerar a rota pública.

### Métricas não aparecem

As métricas são registradas apenas em requisições `GET` de páginas públicas. Arquivos estáticos e rotas `/admin` são ignorados.

## Checklist de Produção

- [ ] Alterar senha do usuário `admin`.
- [ ] Validar HTTPS no domínio.
- [ ] Garantir que `db/cms.db` não seja baixável publicamente.
- [ ] Garantir que `backups/` não exponha listagem ou downloads indevidos.
- [ ] Testar criação de post, página, imagem e backup.
- [ ] Testar cadastro e login de leitor.
- [ ] Testar login administrativo com captcha.
- [ ] Criar backup inicial após configuração.
- [ ] Revisar permissões de diretórios graváveis.

## Licença

Este repositório aparenta ser um projeto pessoal/privado. Defina uma licença antes de distribuir, reutilizar publicamente ou aceitar contribuições externas.

## Status

Projeto em evolução contínua para o site ChiapettaDev, com foco em autonomia editorial, simplicidade operacional e facilidade de manutenção em hospedagem PHP.
