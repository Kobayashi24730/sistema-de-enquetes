# 📊 Sistema de Enquetes

Sistema completo de enquetes online desenvolvido como parte do Desafio Técnico - Nível Trainee/Júnior. Permite que usuários criem enquetes, votem e visualizem resultados em tempo real.

![React](https://img.shields.io/badge/React-19-61dafb?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.1+-777bb4?style=flat-square)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479a1?style=flat-square)
![Vite](https://img.shields.io/badge/Vite-6-646cff?style=flat-square)
![Tailwind](https://img.shields.io/badge/Tailwind-4-06b6d4?style=flat-square)

## ✨ Funcionalidades

### 🔐 Autenticação
- Cadastro de usuários (nome, email, senha)
- Login/Logout com JWT (24h de expiração)
- Proteção de rotas
- Recuperação de senha via email

### 📝 Gestão de Enquetes
- Criar enquetes com título, descrição, categoria e 2-8 opções
- Listar todas as enquetes públicas
- Visualizar detalhes de uma enquete
- Editar e excluir enquetes (apenas o criador)
- Filtrar por categoria e ordenar (Recentes/Populares)
- Busca por título

### 🗳️ Votação
- Votar em enquetes de outros usuários
- Um voto por usuário por enquete
- Votação anônima permitida
- Notificação por email ao criador

### 🔄 Real-time (SSE)
- Resultados atualizados automaticamente
- Stream via Server-Sent Events
- Sem necessidade de refresh

### 📈 Visualização
- Gráficos de resultados (Recharts)
- Barras de progresso
- Percentuais de votos
- Total de votos por enquete

## 🛠️ Stack Tecnológica

### Frontend
- **React 19** - Framework UI
- **Vite 6** - Build tool
- **Tailwind CSS 4** - Estilização
- **shadcn/ui** - Componentes UI
- **React Router 7** - Roteamento
- **Axios** - HTTP client
- **Recharts** - Gráficos
- **Zod** - Validação de schemas
- **React Hook Form** - Gerenciamento de formulários

### Backend
- **PHP 8.1+** - Linguagem (Vanilla, sem framework)
- **PDO** - Acesso ao banco de dados
- **JWT** - Autenticação (firebase/php-jwt)
- **PHPMailer** - Envio de emails
- **Predis** - Cliente Redis para rate limiting

### Banco de Dados
- **MySQL 8.0** - Banco principal
- **Redis** - Rate limiting (Upstash para Vercel)

### Deploy
- **Vercel** - Frontend e Backend
- **Aiven Cloud** - MySQL

## 📁 Estrutura do Projeto

```
sistema-de-enquetes/
├── backend/
│   ├── api/
│   │   └── index.php              # Entry point + roteador
│   ├── src/
│   │   ├── config/
│   │   │   ├── Database.php       # Conexão PDO
│   │   │   └── Migration.php      # Auto-migration
│   │   ├── controllers/
│   │   │   ├── UserController.php         # Login/Registro/Profile
│   │   │   ├── PollController.php         # CRUD de enquetes
│   │   │   ├── VotoController.php         # Processar voto
│   │   │   ├── StreamController.php       # SSE real-time
│   │   │   └── ForgotPasswordController.php
│   │   ├── Middlewares/
│   │   │   ├── AuthMiddleware.php   # Validação JWT
│   │   │   └── RateLimitMiddleware.php  # Rate limiting (Redis)
│   │   └── models/
│   │       └── User.php
│   ├── composer.json
│   ├── database.sql                 # Schema SQL standalone
│   └── vercel.json
├── frontend/
│   ├── src/
│   │   ├── pages/                  # Páginas (Home, Auth, Perfil, etc)
│   │   ├── components/             # Componentes (Header, Cards, Charts)
│   │   ├── context/                # AuthContext (estado global)
│   │   ├── hooks/                  # useRealTime (SSE polling)
│   │   ├── services/               # api.js (Axios instance)
│   │   ├── types/                  # Tipos TypeScript
│   │   └── lib/                    # Utils (cn)
│   ├── package.json
│   └── vercel.json
└── postman_collection.json         # API endpoints
```

## 🚀 Instalação e Execução

### Pré-requisitos
- PHP 8.1+
- Composer
- Node.js 18+
- MySQL 8.0+
- Redis (opcional, para rate limiting)

### Backend

```bash
cd backend

# Instalar dependências
composer install

# Criar arquivo .env
cp .env.example .env

# Editar variáveis de ambiente:
# DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
# JWT_SECRET (gerar chave aleatória)
# MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS
# REDIS_URL (opcional, para rate limiting)

# Iniciar servidor PHP
php -S localhost:8000 api/index.php
```

### Frontend

```bash
cd frontend

# Instalar dependências
npm install

# Criar arquivo .env
cp .env.example .env

# Editar: VITE_API_URL=http://localhost:8000

# Iniciar servidor de desenvolvimento
npm run dev
```

### Banco de Dados

```bash
# Opção 1: Usar Migration automática (recomendado)
# A Migration.php cria as tabelas automaticamente na primeira execução

# Opção 2: Importar database.sql manualmente
mysql -u root -p < backend/database.sql
```

## 🔧 Variáveis de Ambiente

### Backend (.env)

```env
# Database
DB_HOST=seu_host_aiven
DB_PORT=18742
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASS=sua_senha_aqui

# JWT
JWT_SECRET=gerar_chave_secreta_aleatoria_aqui

# Email (PHPMailer)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=seu_email@gmail.com
MAIL_PASS=sua_senha_de_app_aqui

# Frontend URL (para links de recuperação de senha)
CLIENT_URL=http://localhost:3000

# Redis (opcional, para rate limiting)
REDIS_URL=redis://default:senha@host:6379
```

### Frontend (.env)

```env
VITE_API_URL=http://localhost:8000
```

## 📡 API Endpoints

### Autenticação
| Método | Rota | Descrição | Auth |
|--------|------|-----------|------|
| POST | `/register` | Criar conta | ❌ |
| POST | `/login` | Login (retorna JWT) | ❌ |
| GET | `/profile` | Dados do usuário | ✅ |

### Enquetes
| Método | Rota | Descrição | Auth |
|--------|------|-----------|------|
| GET | `/enquetes` | Listar todas | ❌ |
| POST | `/enquetes` | Criar enquete | ✅ |
| GET | `/enquetes/show?id=X` | Ver detalhes | ⚠️ |
| PUT | `/enquetes/update?id=X` | Atualizar | ✅ (criador) |
| DELETE | `/enquetes/delete?id=X` | Excluir | ✅ (criador) |
| POST | `/enquetes/vote` | Votar | ⚠️ |

### Recuperação de Senha
| Método | Rota | Descrição | Auth |
|--------|------|-----------|------|
| POST | `/forgot-password` | Enviar email | ❌ |
| POST | `/reset-password` | Resetar senha | ❌ (token) |

### Real-time
| Método | Rota | Descrição | Auth |
|--------|------|-----------|------|
| GET | `/stream?poll_id=X` | SSE stream | ❌ |

**Legenda:** ✅ Obrigatório | ⚠️ Opcional | ❌ Não requer

**Documentação completa:** Importar `postman_collection.json` no Postman

## 📊 Schema do Banco de Dados

```sql
users
├── id (INT, PK)
├── name (VARCHAR 255)
├── email (VARCHAR 255, UNIQUE)
├── password (VARCHAR 255, bcrypt)
└── created_at (TIMESTAMP)

enquetes
├── id (INT, PK)
├── user_id (INT, FK → users)
├── title (VARCHAR 255)
├── description (TEXT)
├── category (VARCHAR 100)
├── expires_at (DATETIME)
└── created_at (TIMESTAMP)

enquetes_options
├── id (INT, PK)
├── enquete_id (INT, FK → enquetes)
├── option_text (VARCHAR 255)
└── votes (INT, DEFAULT 0)

enquete_votos
├── id (INT, PK)
├── enquete_id (INT, FK → enquetes)
├── option_id (INT, FK → enquetes_options)
├── user_id (INT, FK → users)
├── created_at (TIMESTAMP)
└── UNIQUE (user_id, enquete_id)

password_resets
├── id (INT, PK)
├── email (VARCHAR 255)
├── token (VARCHAR 255)
├── created_at (TIMESTAMP)
└── expires_at (DATETIME)
```

## 🌐 Deploy na Vercel

### Backend
1. Conectar repositório GitHub no Vercel
2. Configurar variáveis de ambiente (Settings → Environment Variables)
3. Deploy automático via push

### Frontend
1. Conectar repositório GitHub no Vercel
2. Configurar `VITE_API_URL` com URL do backend
3. Deploy automático via push

### Banco de Dados
- **MySQL:** Aiven Cloud (gratuito)
- **Redis:** Upstash (gratuito para rate limiting)

## 🔒 Segurança

- ✅ SQL Injection: PDO prepared statements
- ✅ XSS: React escapa outputs automaticamente
- ✅ JWT: Tokens com expiração de 24h
- ✅ Rate Limiting: 5 votos/minuto por IP (Redis)
- ✅ Validação: Zod (frontend) + PHP (backend)
- ✅ Ownership: Apenas criador pode editar/excluir
- ✅ CORS: Configurado para domínios específicos
- ✅ Senhas: Hash bcrypt (60 rounds)

## 📱 Telas

1. **Home** - Listagem de enquetes com filtros e busca
2. **Auth** - Login/Registro (form dual)
3. **Perfil** - Dados do usuário
4. **Criar Enquete** - Formulário com validação
5. **Editar Enquete** - Atualizar enquete existente
6. **Detalhe da Enquete** - Votar, ver resultados, gráfico, compartilhar
7. **Recuperar Senha** - Solicitar reset por email
8. **Resetar Senha** - Definir nova senha com token
9. **404** - Página não encontrada

## 🎯 Funcionalidades Implementadas

### Obrigatórias
- ✅ Cadastro/Login/Logout
- ✅ CRUD completo de enquetes
- ✅ Sistema de votação (1 voto por usuário)
- ✅ Resultados em tempo real (SSE)
- ✅ Proteção de rotas
- ✅ Recuperação de senha

### Opcionais
- ✅ Notificações por email
- ✅ Gráficos de resultados
- ✅ Filtros e busca
- ✅ Compartilhamento via link
- ✅ Rate limiting
- ✅ Responsividade (mobile-first)

## 📝 Decisões Técnicas

1. **PHP Vanilla** - Escolhido para demonstrar conhecimento fundamental
2. **SSE vs WebSocket** - SSE é mais simples e adequado para unidirecional
3. **Redis para Rate Limiting** - Compatível com Vercel (Upstash)
4. **Migration Automática** - Facilita deploy e desenvolvimento
5. **JWT Stateless** - Não requer sessão no servidor
6. **Tailwind + shadcn** - Rápido desenvolvimento com componentes acessíveis

## 🐛 Problemas Conhecidos

- Rate limiting não funciona sem Redis configurado (fallback permite todas requisições)
- Links de recuperação de senha usam CLIENT_URL (configurar no .env)
- Votos são perdidos ao editar enquete (opções são recriadas)

## 📚 Aprendizados

- Implementação de SSE em PHP
- Rate limiting distribuído com Redis
- Auto-migration com PHP PDO
- Integração React + PHP com JWT
- Deploy serverless na Vercel (PHP + React)

## 📄 Licença

Este projeto foi desenvolvido como parte de um desafio técnico e está disponível para fins educacionais.

## 👨‍💻 Autor

**Kobayashi24730** - [GitHub](https://github.com/Kobayashi24730)

Desafio Técnico - Nível Trainee/Júnior

---

**Status do Projeto:** ✅ Funcional e em produção

**Última atualização:** Julho 2026
