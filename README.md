# Gestão de Cadastro de Colaboradores

API RESTful desenvolvida em Laravel 12 para gestão de colaboradores, com autenticação, controle de acesso por roles, suporte multi-idioma, cache Redis e testes automatizados.

## Estrutura do Projeto

```
.
├── src/                    # Código fonte do Laravel
├── .docker/                # Configurações Docker
│   ├── nginx/             # Configuração Nginx
│   ├── php/               # Configuração PHP
│   ├── supervisor/        # Configuração Supervisor (filas)
│   ├── scripts/           # Scripts de inicialização (app-entrypoint.sh)
│   └── Dockerfile         # Imagem da aplicação
├── docker-compose.yml     # Orquestração dos containers
├── .env.example          # Variáveis de ambiente de exemplo
├── setup.sh              # Script de configuração completa
├── install-laravel.sh    # Script de instalação do Laravel
└── README.md             # Este arquivo
```

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

# Executar testes com coverage
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

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT.