# API Documentation - Convenia

## 📋 Visão Geral

Sistema de gerenciamento de colaboradores e usuários com autenticação JWT (Laravel Passport), importação de CSV e sistema de permissões baseado em roles.

### 🔧 Tecnologias
- **Laravel 12** com PHP 8.3
- **Laravel Passport** para autenticação JWT
- **Spatie Permission** para controle de acesso
- **Docker** para ambiente de desenvolvimento
- **MailHog** para testes de email

### 🌐 Base URL
```
http://localhost:8000/api
```

### 🔐 Autenticação
A API usa **Bearer Token** (JWT) para autenticação. Inclua o token no header:
```
Authorization: Bearer {access_token}
```

### 👥 Usuários de Teste (Seeder)
O sistema vem com usuários pré-configurados no `UserSeeder`:

#### Gestores (podem acessar a API):
- **Gestor Principal**: `gestor@convenia.com` / `password`
- **Gestor Secundário**: `gestor2@convenia.com` / `password`

#### Colaborador (NÃO pode acessar a API):
- **Colaborador Teste**: `colaborador@convenia.com` / `password`

> ⚠️ **Importante**: Apenas usuários com role `manager` podem fazer login na API.

---

## 🚀 Endpoints

### 🔑 Autenticação

#### POST `/auth/login`
Realizar login no sistema

**Corpo da Requisição:**
```json
{
    "email": "gestor@convenia.com",
    "password": "password"
}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Login realizado com sucesso",
    "data": {
        "user": {
            "id": 1,
            "name": "Gestor Principal",
            "email": "gestor@convenia.com",
            "roles": ["manager"],
            "permissions": ["manage users", "manage collaborators"]
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
    }
}
```

**Validações:**
- `email`: obrigatório, email válido
- `password`: obrigatório, mínimo 8 caracteres

**Regras de Negócio:**
- Apenas usuários com role `manager` podem fazer login
- Credenciais inválidas retornam erro 401
- Usuários sem permissão retornam erro 403

---

#### POST `/auth/logout`
Realizar logout (requer autenticação)

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Logout realizado com sucesso",
    "data": []
}
```

---

#### GET `/auth/me`
Obter informações do usuário autenticado

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": {
        "id": 1,
        "name": "Gestor Principal",
        "email": "gestor@convenia.com",
        "roles": ["manager"],
        "permissions": ["manage users", "manage collaborators"],
        "created_at": "2025-10-29T10:00:00.000000Z"
    }
}
```

---

### 👥 Gerenciamento de Usuários

#### GET `/users`
Listar todos os usuários

**Headers:**
```
Authorization: Bearer {access_token}
```

**Parâmetros de Query:**
- `page`: número da página (padrão: 1)
- `per_page`: itens por página (padrão: 15, máx: 100)

**Resposta de Sucesso (200):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Manager User",
            "email": "manager@example.com",
            "roles": ["manager"],
            "created_at": "2025-10-29T10:00:00.000000Z"
        }
    ],
    "links": {
        "first": "http://localhost:8000/api/users?page=1",
        "last": "http://localhost:8000/api/users?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

---

#### POST `/users`
Criar novo usuário

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Corpo da Requisição:**
```json
{
    "name": "Novo Usuário",
    "email": "novo@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Resposta de Sucesso (201):**
```json
{
    "success": true,
    "message": "Usuário criado com sucesso",
    "data": {
        "id": 2,
        "name": "Novo Usuário",
        "email": "novo@example.com",
        "roles": ["manager"],
        "created_at": "2025-10-29T11:00:00.000000Z"
    }
}
```

**Validações:**
- `name`: obrigatório, string, máximo 255 caracteres
- `email`: obrigatório, email único
- `password`: obrigatório, mínimo 8 caracteres, confirmação obrigatória

---

#### GET `/users/{id}`
Obter usuário específico

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": {
        "id": 1,
        "name": "Manager User",
        "email": "manager@example.com",
        "roles": ["manager"],
        "created_at": "2025-10-29T10:00:00.000000Z"
    }
}
```

---

#### PUT `/users/{id}`
Atualizar usuário

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Corpo da Requisição:**
```json
{
    "name": "Nome Atualizado",
    "email": "email_atualizado@example.com"
}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Usuário atualizado com sucesso",
    "data": {
        "id": 1,
        "name": "Nome Atualizado",
        "email": "email_atualizado@example.com",
        "roles": ["manager"],
        "updated_at": "2025-10-29T12:00:00.000000Z"
    }
}
```

---

#### DELETE `/users/{id}`
Deletar usuário

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Usuário deletado com sucesso",
    "data": []
}
```

---

#### GET `/users/search/{search}`
Buscar usuários por nome ou email

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Manager User",
            "email": "manager@example.com",
            "roles": ["manager"],
            "created_at": "2025-10-29T10:00:00.000000Z"
        }
    ]
}
```

---

#### GET `/users/statistics/overview`
Obter estatísticas dos usuários

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": {
        "total_users": 10,
        "active_users": 8,
        "managers": 3,
        "recent_registrations": 2
    }
}
```

---

### 👷 Gerenciamento de Colaboradores

#### GET `/collaborators`
Listar todos os colaboradores

**Headers:**
```
Authorization: Bearer {access_token}
```

**Parâmetros de Query:**
- `page`: número da página (padrão: 1)
- `per_page`: itens por página (padrão: 15, máx: 100)
- `city`: filtrar por cidade

**Resposta de Sucesso (200):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "João Silva",
            "email": "joao@example.com",
            "cpf": "123.456.789-00",
            "phone": "(11) 99999-9999",
            "city": "São Paulo",
            "created_at": "2025-10-29T10:00:00.000000Z"
        }
    ],
    "links": {
        "first": "http://localhost:8000/api/collaborators?page=1",
        "last": "http://localhost:8000/api/collaborators?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

---

#### POST `/collaborators`
Criar novo colaborador

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Corpo da Requisição:**
```json
{
    "name": "Maria Santos",
    "email": "maria@example.com",
    "cpf": "987.654.321-00",
    "phone": "(11) 88888-8888",
    "city": "Rio de Janeiro"
}
```

**Resposta de Sucesso (201):**
```json
{
    "success": true,
    "message": "Colaborador criado com sucesso",
    "data": {
        "id": 2,
        "name": "Maria Santos",
        "email": "maria@example.com",
        "cpf": "987.654.321-00",
        "phone": "(11) 88888-8888",
        "city": "Rio de Janeiro",
        "created_at": "2025-10-29T11:00:00.000000Z"
    }
}
```

**Validações:**
- `name`: obrigatório, string, máximo 255 caracteres
- `email`: obrigatório, email único
- `cpf`: obrigatório, CPF válido e único
- `phone`: opcional, string
- `city`: obrigatório, string

---

#### GET `/collaborators/{id}`
Obter colaborador específico

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": {
        "id": 1,
        "name": "João Silva",
        "email": "joao@example.com",
        "cpf": "123.456.789-00",
        "phone": "(11) 99999-9999",
        "city": "São Paulo",
        "created_at": "2025-10-29T10:00:00.000000Z"
    }
}
```

---

#### PUT `/collaborators/{id}`
Atualizar colaborador

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Corpo da Requisição:**
```json
{
    "name": "João Silva Santos",
    "phone": "(11) 99999-8888",
    "city": "São Paulo"
}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Colaborador atualizado com sucesso",
    "data": {
        "id": 1,
        "name": "João Silva Santos",
        "email": "joao@example.com",
        "cpf": "123.456.789-00",
        "phone": "(11) 99999-8888",
        "city": "São Paulo",
        "updated_at": "2025-10-29T12:00:00.000000Z"
    }
}
```

---

#### DELETE `/collaborators/{id}`
Deletar colaborador

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Colaborador deletado com sucesso",
    "data": []
}
```

---

#### GET `/collaborators/statistics/overview`
Obter estatísticas dos colaboradores

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": {
        "total_collaborators": 150,
        "active_collaborators": 142,
        "cities_count": 25,
        "recent_registrations": 8
    }
}
```

---

### 📊 Importação e Exportação de Colaboradores

#### GET `/collaborators/import`
Listar importações do usuário

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": [
        {
            "id": 1,
            "filename": "colaboradores_2025.csv",
            "status": "completed",
            "total_rows": 100,
            "processed_rows": 95,
            "success_rows": 90,
            "error_rows": 5,
            "created_at": "2025-10-29T10:00:00.000000Z"
        }
    ]
}
```

---

#### POST `/collaborators/import`
Fazer upload de arquivo CSV para importação

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
```

**Corpo da Requisição:**
```
file: [arquivo CSV]
```

**Resposta de Sucesso (201):**
```json
{
    "success": true,
    "message": "Arquivo enviado para processamento",
    "data": {
        "import_id": 2,
        "filename": "novos_colaboradores.csv",
        "status": "processing",
        "total_rows": 50
    }
}
```

**Validações:**
- Arquivo obrigatório
- Formato CSV
- Tamanho máximo: 2MB
- Campos obrigatórios: name, email, cpf, city

---

#### POST `/collaborators/import/validate`
Validar estrutura do CSV antes da importação

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
```

**Corpo da Requisição:**
```
file: [arquivo CSV]
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Arquivo válido para importação",
    "data": {
        "valid": true,
        "total_rows": 50,
        "headers": ["name", "email", "cpf", "phone", "city"],
        "missing_fields": [],
        "sample_data": [
            {
                "name": "João Silva",
                "email": "joao@example.com",
                "cpf": "123.456.789-00",
                "city": "São Paulo"
            }
        ]
    }
}
```

---

#### GET `/collaborators/import/template`
Baixar template CSV para importação

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta:** Arquivo CSV com headers:
```csv
name,email,cpf,phone,city
```

---

#### GET `/collaborators/import/{import}/status`
Verificar status de uma importação

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "data": {
        "id": 1,
        "filename": "colaboradores_2025.csv",
        "status": "completed",
        "total_rows": 100,
        "processed_rows": 100,
        "success_rows": 95,
        "error_rows": 5,
        "errors": [
            {
                "row": 10,
                "field": "email",
                "message": "Email já existe"
            }
        ],
        "created_at": "2025-10-29T10:00:00.000000Z",
        "completed_at": "2025-10-29T10:15:00.000000Z"
    }
}
```

---

#### POST `/collaborators/import/{import}/cancel`
Cancelar importação em processamento

**Headers:**
```
Authorization: Bearer {access_token}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Importação cancelada com sucesso",
    "data": {
        "id": 1,
        "status": "cancelled"
    }
}
```

---

#### POST `/collaborators/export`
Exportar colaboradores para CSV

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Corpo da Requisição (opcional):**
```json
{
    "filters": {
        "city": "São Paulo",
        "created_from": "2025-01-01",
        "created_to": "2025-12-31"
    }
}
```

**Resposta:** Arquivo CSV com dados dos colaboradores

---

## 📝 Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Dados inválidos |
| 401 | Não autenticado |
| 403 | Sem permissão |
| 404 | Não encontrado |
| 422 | Erro de validação |
| 500 | Erro interno do servidor |

---

## 🔍 Estrutura de Erros

```json
{
    "success": false,
    "message": "Mensagem de erro",
    "error_code": "VALIDATION_ERROR",
    "errors": {
        "email": ["O campo email é obrigatório"]
    }
}
```

---

## 📧 Notificações por Email

O sistema envia emails automáticos para:
- ✅ **Importação concluída**: Quando o processamento do CSV termina
- ✅ **Importação com erros**: Quando há falhas no processamento
- ✅ **Novo usuário**: Quando um usuário é criado (email de boas-vindas)

**Configuração de Email:**
- Ambiente de desenvolvimento: MailHog (http://localhost:8025)
- Emails em fila para performance

---

## 🧪 Ambiente de Desenvolvimento

### Docker
```bash
# Iniciar ambiente
docker-compose up -d

# Executar testes
docker-compose exec app php artisan test

# Acessar container
docker-compose exec app bash
```

### URLs Importantes
- **API**: http://localhost:8000/api
- **MailHog**: http://localhost:8025 (visualizar emails)

---

## 🔐 Segurança

### Autenticação
- **JWT Tokens** via Laravel Passport
- **Refresh Tokens** para renovação automática
- **Rate Limiting** nas rotas de login

### Autorização
- **Role-based** (manager, collaborator)
- **Permission-based** (manage users, manage collaborators)
- **Políticas** para controle granular de acesso

### Validações
- **CSRF Protection** desabilitado para API
- **Input Validation** em todas as rotas
- **SQL Injection** prevenção via Eloquent ORM

---

## 📚 Recursos Adicionais

### Paginação
Todas as listagens suportam paginação automática com meta informações.

### Filtros
- Colaboradores podem ser filtrados por cidade
- Usuários podem ser pesquisados por nome/email

### Performance
- **Queues** para processamento em background
- **Database Indexing** nos campos principais
- **API Resource** para formatação eficiente

---

*Documentação gerada em 29 de outubro de 2025*