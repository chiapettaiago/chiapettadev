# CMS ChiapettaDev - Documentação

## 🚀 Acesso ao Painel Administrativo

**URL:** `https://neverland.chiapetta.dev/admin/`

**Credenciais Padrão:**
- **Usuário:** `admin`
- **Senha:** `admin123`

⚠️ **IMPORTANTE:** Altere a senha após o primeiro login em Configurações > Alterar Senha

---

## 📋 Funcionalidades

### 1. **Dashboard**
- Visualização de estatísticas gerais
- Últimos posts criados
- Imagens recentes
- Atalhos rápidos para principais funções

### 2. **Gerenciador de Posts (Blog)**
- ✅ Criar, editar e deletar posts
- ✅ Publicar ou manter como rascunho
- ✅ Adicionar imagem destacada
- ✅ Adicionar tags e categorias
- ✅ Editor com barra de ferramentas (negrito, itálico, títulos, código, etc)
- ✅ URLs amigáveis (slugs) automáticas
- ✅ Contador de visualizações

**Localização dos posts:**
```
/blog/[slug-do-post]/
```

### 3. **Gerenciador de Páginas**
- ✅ Criar, editar e deletar páginas
- ✅ Criar sub-páginas (hierarquia)
- ✅ Ordenar páginas
- ✅ Publicar ou manter como rascunho
- ✅ Imagem destacada

**Localização das páginas:**
```
/[slug-da-pagina]/
```

### 4. **Gerenciador de Imagens**
- ✅ Upload de imagens (JPG, PNG, GIF, WEBP)
- ✅ Limite de 5MB por arquivo
- ✅ Compressão e otimização automática
- ✅ Redimensionamento automático (máximo 1920x1920)
- ✅ Gerenciar metadados (título, alt text, descrição)
- ✅ Galeria com pré-visualização
- ✅ Copiar imagens para pasta pública

**Armazenamento:**
- Uploads: `/admin/uploads/`
- Públicas: `/images/`

### 5. **Gerenciador de Usuários**
- ✅ Criar novos usuários
- ✅ Atribuir papéis (roles): Author, Editor, Admin
- ✅ Gerenciar status de usuários

**Papéis e Permissões:**
- **Author:** Criar seus próprios posts
- **Editor:** Criar, editar e deletar posts
- **Admin:** Acesso completo ao sistema

### 6. **Configurações**
- ✅ Alterar senha
- ✅ Configurações do site (título, descrição, URL)
- ✅ Itens por página
- ✅ Informações do sistema

---

## 🗄️ Estrutura do Banco de Dados

**Arquivo:** `/db/cms.db` (SQLite)

**Tabelas principais:**
- `users` - Usuários do sistema
- `posts` - Posts do blog
- `pages` - Páginas do site
- `images` - Imagens enviadas
- `tags` - Tags para posts
- `post_tags` - Relacionamento posts-tags
- `categories` - Categorias de posts
- `post_categories` - Relacionamento posts-categorias
- `settings` - Configurações gerais

---

## 📝 Como Usar

### Criar um Novo Post

1. Acesse **Posts** no menu lateral
2. Clique em **Novo Post**
3. Preencha:
   - **Título** (obrigatório)
   - **Conteúdo** (obrigatório)
   - **Resumo** (opcional)
   - **Imagem Destacada** (opcional)
   - **Tags** (separe com vírgula)
   - **Status** (Rascunho ou Publicado)
4. Clique em **Criar Post**

### Criar uma Nova Página

1. Acesse **Páginas** no menu lateral
2. Clique em **Nova Página**
3. Preencha:
   - **Título** (obrigatório)
   - **Conteúdo** (obrigatório)
   - **Página Principal** (para subpáginas)
   - **Ordem** (número para ordenação)
   - **Status** (Rascunho ou Publicado)
4. Clique em **Criar Página**

### Fazer Upload de Imagens

1. Acesse **Imagens** no menu lateral
2. Clique ou arraste imagens para a área de upload
3. Preencha:
   - **Título** (obrigatório)
   - **Texto Alternativo** (importante para SEO)
   - **Descrição** (opcional)
4. Clique em **Upload**

### Criar um Novo Usuário

1. Acesse **Usuários** (apenas para admins)
2. Preencha o formulário à esquerda:
   - **Nome de Usuário**
   - **Email**
   - **Nome Completo**
   - **Senha**
   - **Papel** (Author, Editor, Admin)
3. Clique em **Criar Usuário**

---

## 🔐 Segurança

- ✅ Senhas criptografadas com bcrypt
- ✅ Validação de arquivo de upload
- ✅ Proteção contra SQL Injection
- ✅ Sessões com timeout automático (1 hora)
- ✅ Suporte a múltiplos usuários
- ✅ Sistema de papéis e permissões

---

## 📂 Estrutura de Arquivos

```
/admin/
├── login.php              # Página de login
├── dashboard.php          # Painel principal
├── logout.php            # Logout
├── modules/
│   ├── Auth.php          # Sistema de autenticação
│   ├── Post.php          # Gerenciar posts
│   ├── Page.php          # Gerenciar páginas
│   └── Image.php         # Gerenciar imagens
├── pages/
│   ├── posts.php         # Listar/editar posts
│   ├── pages.php         # Listar/editar páginas
│   ├── images.php        # Gerenciar imagens
│   ├── users.php         # Gerenciar usuários
│   └── settings.php      # Configurações
└── uploads/              # Imagens enviadas

/db/
├── config.php            # Configuração do banco
├── init.php              # Inicialização do banco
└── cms.db                # Arquivo SQLite (criado automaticamente)
```

---

## 🐛 Solução de Problemas

### "Erro de conexão com banco de dados"
- Verifique se a pasta `/db/` tem permissão de escrita (755)
- Certifique-se de que o PHP tem suporte para SQLite

### "Upload não funciona"
- Verifique se `/admin/uploads/` tem permissão de escrita (755)
- Verifique o tamanho máximo de upload PHP (`php.ini`)
- Certifique-se de que está usando apenas JPG, PNG, GIF ou WEBP

### "Não consigo fazer login"
- Verifique se digitou corretamente usuário e senha
- Se esqueceu a senha, você pode resetá-la via banco de dados

---

## 🔄 Integração com o Site

O CMS está totalmente integrado com o site. Para exibir posts e páginas:

```php
// Obter um post
require_once __DIR__ . '/admin/modules/Post.php';
$post = Post::getBySlug('meu-post');

// Obter uma página
require_once __DIR__ . '/admin/modules/Page.php';
$page = Page::getBySlug('minha-pagina');

// Listar posts
$posts = Post::getList(['status' => 'published', 'limit' => 10]);
```

---

## 📞 Suporte

Para dúvidas ou problemas com o CMS, entre em contato através do formulário no site ou via email.

---

**Última atualização:** Maio de 2026
**Versão:** 1.0.0
