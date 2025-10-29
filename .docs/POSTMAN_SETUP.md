# 🚀 Configuração do Postman - Convenia API

## 📁 Arquivos Gerados

Na raiz do projeto você encontrará:

1. **`API_DOCUMENTATION.md`** - Documentação completa da API
2. **`Convenia_API_Collection.postman_collection.json`** - Collection do Postman com todos os endpoints
3. **`Convenia_API_Development.postman_environment.json`** - Environment para desenvolvimento

---

## 🔧 Como Importar no Postman

### 1. Importar Environment
1. Abra o Postman
2. Clique em **"Import"** (canto superior esquerdo)
3. Selecione o arquivo `Convenia_API_Development.postman_environment.json`
4. Clique em **"Import"**

### 2. Importar Collection
1. No Postman, clique em **"Import"** novamente
2. Selecione o arquivo `Convenia_API_Collection.postman_collection.json`
3. Clique em **"Import"**

### 3. Selecionar Environment
1. No canto superior direito, clique no dropdown de environments
2. Selecione **"Convenia API - Development"**

---

## ⚙️ Configuração do Environment

O environment já vem pré-configurado com os **usuários do seeder**:

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `base_url` | `http://localhost:8000/api` | URL base da API |
| `access_token` | `(vazio)` | Token JWT (preenchido automaticamente) |
| `manager_email` | `gestor@convenia.com` | Email do gestor principal |
| `manager_password` | `password` | Senha do gestor principal |
| `manager2_email` | `gestor2@convenia.com` | Email do gestor secundário |
| `manager2_password` | `password` | Senha do gestor secundário |
| `collaborator_email` | `colaborador@convenia.com` | Email do colaborador (apenas para referência) |
| `collaborator_password` | `password` | Senha do colaborador (não pode fazer login na API) |
| `content_type` | `application/json` | Content-Type padrão |
| `accept` | `application/json` | Accept header padrão |

### 👥 Usuários Disponíveis (UserSeeder)

#### Gestores (podem fazer login na API):
1. **Gestor Principal**
   - Email: `gestor@convenia.com`
   - Senha: `password`
   - Role: `manager`

2. **Gestor Secundário**
   - Email: `gestor2@convenia.com`
   - Senha: `password`
   - Role: `manager`

#### Colaborador (NÃO pode fazer login na API):
- **Colaborador Teste**
  - Email: `colaborador@convenia.com`
  - Senha: `password`
  - Role: `collaborator` (sem acesso à API)

---

## 🎯 Como Usar

### 1. Fazer Login
1. Navegue até **"🔑 Authentication" > "Login"**
2. Execute a requisição
3. O token será **automaticamente salvo** na variável `access_token`

### 2. Testar Endpoints
Agora você pode executar qualquer endpoint! Eles usarão automaticamente o token salvo.

### 3. Endpoints Organizados por Categoria

#### 🔑 Authentication
- Login (salva token automaticamente)
- Logout
- Get Current User

#### 👥 Users Management
- List Users (com paginação)
- Create User
- Get User
- Update User
- Delete User
- Search Users
- User Statistics

#### 👷 Collaborators Management
- List Collaborators (com filtros)
- Create Collaborator
- Get Collaborator
- Update Collaborator
- Delete Collaborator
- Collaborator Statistics

#### 📊 Import & Export
- List Imports
- Upload CSV for Import
- Validate CSV
- Download CSV Template
- Check Import Status
- Cancel Import
- Export Collaborators

---

## 🔒 Autenticação Automática

Todas as requisições (exceto login) já estão configuradas com:
```
Authorization: Bearer {{access_token}}
```

O token é preenchido automaticamente após o login bem-sucedido.

---

## 📝 Scripts Automáticos

### Script de Login
Após fazer login, este script salva automaticamente o token:
```javascript
if (pm.response.code === 200) {
    const response = pm.response.json();
    if (response.success && response.data.token) {
        pm.environment.set('access_token', response.data.token);
        console.log('Token salvo:', response.data.token);
    }
}
```

### Scripts Globais
- **Pre-request**: Log da URL sendo executada
- **Test**: Log do status e tempo de resposta

---

## 🧪 Testando Diferentes Cenários

### 1. Teste de Usuários
```json
{
    "name": "João Silva",
    "email": "joao@test.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### 2. Teste de Colaboradores
```json
{
    "name": "Maria Santos",
    "email": "maria@test.com",
    "cpf": "123.456.789-00",
    "phone": "(11) 99999-9999",
    "city": "São Paulo"
}
```

### 3. Upload de CSV
Para testar importação:
1. Use o endpoint **"Download CSV Template"** para baixar o modelo
2. Preencha com dados de teste
3. Use **"Upload CSV for Import"** para fazer upload

---

## 🔍 Validações e Erros

### Códigos de Status Esperados
- **200**: Sucesso
- **201**: Criado
- **400**: Dados inválidos
- **401**: Token inválido/expirado
- **403**: Sem permissão
- **404**: Não encontrado
- **422**: Erro de validação

### Estrutura de Erro Padrão
```json
{
    "success": false,
    "message": "Mensagem do erro",
    "error_code": "VALIDATION_ERROR",
    "errors": {
        "email": ["O campo email é obrigatório"]
    }
}
```

---

## ⚡ Dicas de Uso

### 1. Paginação
Use os parâmetros `page` e `per_page` nas listagens:
```
{{base_url}}/users?page=1&per_page=15
```

### 2. Filtros
Para colaboradores, você pode filtrar por cidade:
```
{{base_url}}/collaborators?city=São Paulo
```

### 3. Upload de Arquivos
Para endpoints de upload, use **form-data** e selecione o arquivo na aba **Body**.

### 4. Token Expirado
Se receber erro 401, execute novamente o endpoint de **Login**.

---

## 🐳 Ambiente de Desenvolvimento

Certifique-se de que o ambiente Docker está rodando:

```bash
# Iniciar ambiente
docker-compose up -d

# Verificar se está rodando
docker-compose ps

# Logs da aplicação
docker-compose logs app
```

### URLs Importantes
- **API**: http://localhost:8000/api
- **MailHog** (emails): http://localhost:8025

---

## 📧 Testando Emails

1. Execute uma importação de CSV
2. Acesse http://localhost:8025
3. Verifique o email de notificação no MailHog

---

## 🔧 Troubleshooting

### Problema: Token não é salvo automaticamente
**Solução**: Verifique se o environment está selecionado e execute o login novamente.

### Problema: Erro 404 nos endpoints
**Solução**: Verifique se a `base_url` está correta e o Docker está rodando.

### Problema: Erro 500 nos endpoints
**Solução**: Verifique os logs do Docker:
```bash
docker-compose logs app
```

### Problema: Upload de CSV falha
**Solução**: 
1. Verifique o tamanho do arquivo (máx 2MB)
2. Use o template correto baixado do endpoint
3. Certifique-se de que o Content-Type é `multipart/form-data`

---

## 📚 Próximos Passos

1. **Teste todos os endpoints** seguindo a ordem sugerida
2. **Experimente diferentes dados** para validar as regras de negócio
3. **Teste cenários de erro** (dados inválidos, duplicações, etc.)
4. **Explore a funcionalidade de importação** com arquivos CSV reais

---

*Collection criada em 29 de outubro de 2025*
*Documentação versão 1.0.0*