# Gestão de Cadastro de Colaboradores

API RESTful desenvolvida em Laravel 12 para gestão de colaboradores, com autenticação, controle de acesso por roles, suporte multi-idioma, cache Redis e testes automatizados.

## Estrutura do Projeto

```
.
├── src/                    # Código fonte do Laravel
│   ├── app/               # Código da aplicação
│   ├── config/            # Arquivos de configuração
│   ├── database/          # Migrations, seeders e factories
│   ├── routes/            # Definição de rotas
│   ├── tests/             # Testes automatizados
│   └── ...               # Outros arquivos Laravel
├── .docker/               # Configurações Docker
│   ├── nginx/            # Configuração Nginx
│   ├── php/              # Configuração PHP
│   ├── supervisor/       # Configuração Supervisor (filas)
│   ├── scripts/          # Scripts de inicialização
│   └── Dockerfile        # Imagem da aplicação
├── .docs/                 # 📚 Documentação completa do projeto
│   ├── API_DOCUMENTATION.md              # Documentação da API
│   ├── POSTMAN_SETUP.md                  # Guia do Postman
│   ├── PROJECT_SUMMARY.md                # Resumo do projeto
│   ├── Convenia_API_Collection.postman_collection.json
│   ├── Convenia_API_Development.postman_environment.json
│   └── sample_collaborators.csv          # Arquivo de exemplo
├── .convenia/             # Arquivos do projeto original
├── docker-compose.yml     # Orquestração dos containers
├── .env                   # Variáveis de ambiente
└── README.md             # Este arquivo
```

## 📚 Documentação

Toda a documentação do projeto está localizada na pasta **`.docs/`**:

### 📋 Documentos Principais
- **[API_DOCUMENTATION.md](.docs/API_DOCUMENTATION.md)** - Documentação completa da API
- **[POSTMAN_SETUP.md](.docs/POSTMAN_SETUP.md)** - Guia de configuração do Postman  
- **[PROJECT_SUMMARY.md](.docs/PROJECT_SUMMARY.md)** - Resumo executivo do projeto

### 🚀 Postman Collection
- **[Convenia_API_Collection.postman_collection.json](.docs/Convenia_API_Collection.postman_collection.json)** - Collection completa
- **[Convenia_API_Development.postman_environment.json](.docs/Convenia_API_Development.postman_environment.json)** - Environment de desenvolvimento

### 📊 Arquivos de Teste
- **[sample_collaborators.csv](.docs/sample_collaborators.csv)** - Arquivo CSV de exemplo para importação

### 🎯 Quick Start da API
1. **Base URL**: `http://localhost:8000/api`
2. **Autenticação**: Bearer Token (JWT via Laravel Passport)
3. **Usuários de teste**: 
   - `gestor@convenia.com` / `password` (Manager)
   - `gestor2@convenia.com` / `password` (Manager)
4. **Postman**: Importe os arquivos da pasta `.docs/`

> 💡 **Dica**: Consulte `.docs/API_DOCUMENTATION.md` para documentação detalhada de todos os endpoints.

## Requisitos

- Docker
- Docker Compose

## Instalação e Configuração

### 1. Clone o repositório

```bash
git clone <repository-url>
cd opportunity-convenia
```

### 2. Configuração do ambiente

Copie o arquivo de exemplo de configuração:

```bash
cp .env.example .env
```

Edite o arquivo `.env` conforme necessário. As principais variáveis são:

```env
APP_PORT=8000                    # Porta da aplicação
DB_DATABASE=convenia_db          # Nome do banco
DB_USERNAME=convenia_user        # Usuário do banco
DB_PASSWORD=convenia_pass        # Senha do banco
MAILHOG_WEB_PORT=8025           # Porta do MailHog
```

### 4. Subir os containers

```bash
docker-compose up -d
```

## Acessos

- **Aplicação**: http://localhost:8000 (ou porta definida em `APP_PORT`)
- **MailHog (E-mails)**: http://localhost:8025 (ou porta definida em `MAILHOG_WEB_PORT`)
- **MySQL**: localhost:3306 (ou porta definida em `DB_PORT`)
- **Redis**: localhost:6379 (ou porta definida em `REDIS_PORT`)

### Serviços dos Containers

- **convenia-app**: Aplicação Laravel (Supervisor gerencia PHP-FPM + Filas + Schedule)
- **convenia-nginx**: Servidor web
- **convenia-mysql**: Banco de dados
- **convenia-redis**: Cache e sessões
- **convenia-mailhog**: E-mails de desenvolvimento

### Credenciais do Banco de Dados

As credenciais são definidas no arquivo `.env`:
- **Host**: localhost (ou db dentro dos containers)
- **Porta**: Definida em `DB_PORT` (padrão: 3306)
- **Database**: Definida em `DB_DATABASE` (padrão: convenia_db)
- **Usuário**: Definido em `DB_USERNAME` (padrão: convenia_user)
- **Senha**: Definida em `DB_PASSWORD` (padrão: convenia_pass)

## Comandos Úteis

### Artisan

```bash
# Executar comandos do Artisan
docker-compose exec app php artisan <command>

# Limpar cache
docker-compose exec app php artisan cache:clear

# Otimizar aplicação
docker-compose exec app php artisan optimize
```

### Composer

```bash
# Instalar dependências
docker-compose exec app composer install

# Atualizar dependências
docker-compose exec app composer update

# Instalar nova dependência
docker-compose exec app composer require <package>
```

### Testes

```bash
# Executar todos os testes
docker-compose exec app php artisan test

# Executar testes específicos
docker-compose exec app php artisan test --filter=<TestName>

# Executar testes por suite
docker-compose exec app php artisan test --testsuite=Feature
docker-compose exec app php artisan test --testsuite=Unit

# Executar com relatório detalhado
docker-compose exec app php artisan test --verbose
```

#### 🔒 Isolamento de Testes
- **DatabaseTransactions**: Testes usam transações que fazem rollback automático
- **Dados preservados**: Seeders e dados existentes não são afetados pelos testes
- **71 testes** passando com **334 assertions**
- **Usuários do seeder** permanecem disponíveis após execução dos testes

#### 👥 Usuários de Teste (UserSeeder)
Após executar `docker-compose up -d`, os seguintes usuários estão disponíveis:
- **Gestor Principal**: `gestor@convenia.com` / `password`
- **Gestor Secundário**: `gestor2@convenia.com` / `password`
- **Colaborador**: `colaborador@convenia.com` / `password` (não pode acessar API)
docker-compose exec app php artisan test --coverage
```

### Queue (Processamento em Background)

O Supervisor gerencia todos os processos: PHP-FPM, filas e schedule do Laravel.

```bash
# Ver logs das filas
docker-compose logs -f app

# Ver status de todos os processos do Supervisor
docker-compose exec app supervisorctl status

# Reiniciar PHP-FPM
docker-compose exec app supervisorctl restart php-fpm

# Reiniciar workers
docker-compose exec app supervisorctl restart laravel-worker:*

# Reiniciar schedule
docker-compose exec app supervisorctl restart laravel-schedule

# Processar jobs manualmente (se necessário)
docker-compose exec app php artisan queue:work

# Limpar filas
docker-compose exec app php artisan queue:clear
```

**Configurações de Queue no .env:**
- `QUEUE_WORKERS`: Número de workers (padrão: 3)
- `QUEUE_MEMORY`: Memória limite por worker (padrão: 128MB)
- `QUEUE_TIMEOUT`: Timeout por job (padrão: 60s)

**Configurações de Timezone no .env:**
- `APP_TIMEZONE`: Timezone da aplicação (padrão: America/Sao_Paulo)
- `DB_TIMEZONE`: Timezone do MySQL (padrão: -03:00)

Todos os containers usam a mesma timezone para garantir consistência nos timestamps.

## Estrutura da API

### Autenticação

- `POST /api/auth/login` - Login do usuário
- `POST /api/auth/logout` - Logout do usuário
- `GET /api/auth/me` - Dados do usuário autenticado

### Usuários (Managers)

- `GET /api/users` - Listar managers
- `POST /api/users` - Criar manager
- `GET /api/users/{id}` - Visualizar manager
- `PUT /api/users/{id}` - Atualizar manager
- `DELETE /api/users/{id}` - Excluir manager

### Colaboradores

- `GET /api/collaborators` - Listar colaboradores
- `POST /api/collaborators` - Criar colaborador
- `GET /api/collaborators/{id}` - Visualizar colaborador
- `PUT /api/collaborators/{id}` - Atualizar colaborador
- `DELETE /api/collaborators/{id}` - Excluir colaborador
- `POST /api/collaborators/upload` - Upload de CSV

## Regras de Negócio

- Somente usuários com role 'manager' podem realizar login
- Ao cadastrar um colaborador, ele pertence automaticamente ao usuário logado
- Um usuário só pode visualizar, editar ou excluir seus próprios colaboradores
- Upload de CSV é processado em background com notificação por email

## 🌱 Dados do Seeder (UserSeeder)

O sistema inclui um seeder que cria usuários padrão para desenvolvimento e testes. Estes dados podem ser personalizados conforme necessário:

### 📍 Localização
```bash
src/database/seeders/UserSeeder.php
```

### 👥 Usuários Criados Automaticamente

| Email | Nome | Senha | Role | Acesso API |
|-------|------|-------|------|------------|
| `gestor@convenia.com` | Gestor Principal | `password` | manager | ✅ Sim |
| `gestor2@convenia.com` | Gestor Secundário | `password` | manager | ✅ Sim |
| `colaborador@convenia.com` | Colaborador Teste | `password` | collaborator | ❌ Não |

### ⚙️ Personalizando os Dados

Para alterar os dados padrão, edite o arquivo `src/database/seeders/UserSeeder.php`:

```php
// Exemplo: Alterar dados do primeiro gestor
$manager1 = User::firstOrCreate(
    ['email' => 'seu-email@empresa.com'],        // ← Altere o email
    [
        'name' => 'Seu Nome Personalizado',       // ← Altere o nome
        'password' => Hash::make('sua-senha'),    // ← Altere a senha
    ]
);
```

### 🔄 Aplicando Alterações

Após modificar o seeder, execute os comandos:

```bash
# Recrear o banco com novos dados
docker-compose exec app php artisan migrate:fresh --seed

# Ou apenas executar o seeder específico
docker-compose exec app php artisan db:seed --class=UserSeeder
```

> ⚠️ **Importante**: O comando `migrate:fresh` apaga todos os dados existentes. Use com cuidado em ambiente de desenvolvimento.

### 🎯 Uso nos Testes e Postman

Os dados do seeder são utilizados em:
- **Testes automatizados**: Garantem autenticação consistente
- **Collection do Postman**: Environment já configurado com as credenciais
- **Desenvolvimento**: Usuários prontos para testar a API

## Tecnologias Utilizadas

- **Backend**: Laravel 12
- **Banco de Dados**: MySQL 8.0
- **Cache**: Redis 7
- **Containerização**: Docker & Docker Compose
- **Servidor Web**: Nginx
- **E-mail (dev)**: MailHog

## Desenvolvimento

### Logs

```bash
# Ver logs da aplicação
docker-compose logs app

# Ver logs em tempo real
docker-compose logs -f app

# Ver logs do banco de dados
docker-compose logs db
```

### Banco de Dados

```bash
# Acessar MySQL via terminal
docker-compose exec db mysql -u convenia_user -p convenia_db

# Fazer backup do banco
docker-compose exec db mysqldump -u convenia_user -p convenia_db > backup.sql

# Restaurar backup
docker-compose exec -T db mysql -u convenia_user -p convenia_db < backup.sql
```

### Reset do Ambiente

```bash
# Parar containers
docker-compose down

# Remover volumes (CUIDADO: apaga dados do banco)
docker-compose down -v

# Reconstruir containers
docker-compose up -d --build
```

## 📖 Documentação Completa

Para informações detalhadas sobre a API, configuração do Postman e funcionalidades avançadas, consulte:

- **[📋 Documentação da API](.docs/API_DOCUMENTATION.md)** - Todos os endpoints, validações e exemplos
- **[🚀 Setup do Postman](.docs/POSTMAN_SETUP.md)** - Configuração completa para testes
- **[📊 Resumo do Projeto](.docs/PROJECT_SUMMARY.md)** - Visão geral e estatísticas

### 🎯 Funcionalidades Principais
- ✅ **Autenticação JWT** via Laravel Passport
- ✅ **CRUD completo** de usuários e colaboradores
- ✅ **Importação/Exportação CSV** com processamento em background
- ✅ **Sistema de emails** com templates responsivos
- ✅ **Controle de acesso** baseado em roles e permissões
- ✅ **71 testes automatizados** com isolamento de dados
- ✅ **Documentação completa** com collection do Postman

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT.