<?php

return [
    // Auth messages
    'auth' => [
        'login_success' => 'Login realizado com sucesso',
        'login_failed' => 'Falha ao realizar login',
        'logout_success' => 'Logout realizado com sucesso',
        'logout_failed' => 'Falha ao realizar logout',
        'user_data_retrieved' => 'Dados do usuário recuperados com sucesso',
        'invalid_credentials' => 'As credenciais fornecidas estão incorretas.',
        'access_denied_managers_only' => 'Apenas gestores podem realizar login.',
        'token_invalid' => 'Token de acesso inválido ou expirado.',
        'access_denied_managers_resource' => 'Acesso negado. Apenas gestores podem acessar este recurso.',
        'access_denied_unauthenticated' => 'Acesso negado. Você precisa estar autenticado.',
        'access_denied_insufficient_privileges' => 'Acesso negado. Privilégios insuficientes.',
    ],

    // Validation messages
    'validation' => [
        'email_required' => 'O campo email é obrigatório.',
        'email_format' => 'O email deve ter um formato válido.',
        'email_max' => 'O email não pode ter mais de :max caracteres.',
        'email_unique' => 'Este email já está sendo usado.',
        'password_required' => 'O campo senha é obrigatório.',
        'password_string' => 'A senha deve ser uma string.',
        'password_min' => 'A senha deve ter pelo menos :min caracteres.',
        'password_max' => 'A senha não pode ter mais de :max caracteres.',
        'name_required' => 'O campo nome é obrigatório.',
        'name_string' => 'O nome deve ser uma string.',
        'name_max' => 'O nome não pode ter mais de :max caracteres.',
        'role_required' => 'O campo função é obrigatório.',
        'role_in' => 'A função deve ser gestor ou colaborador.',
        'roles_required' => 'O campo funções é obrigatório.',        'roles_array' => 'As funções devem ser um array.',        'roles_invalid' => 'Uma ou mais funções são inválidas.',        'password_confirmed' => 'A confirmação da senha não confere.',
    ],

    // Error codes
    'errors' => [
        'validation_error' => 'VALIDATION_ERROR',
        'invalid_credentials' => 'INVALID_CREDENTIALS',
        'access_denied' => 'ACCESS_DENIED',
        'unauthenticated' => 'UNAUTHENTICATED',
        'forbidden' => 'FORBIDDEN',
        'not_found' => 'NOT_FOUND',
        'internal_server_error' => 'INTERNAL_SERVER_ERROR',
        'database_error' => 'DATABASE_ERROR',
    ],

    // General messages
    'general' => [
        'success' => 'Operação realizada com sucesso',
        'error' => 'Erro na operação',
        'created' => 'Criado com sucesso',
        'updated' => 'Atualizado com sucesso',
        'deleted' => 'Excluído com sucesso',
        'retrieved' => 'Dados recuperados com sucesso',
        'unauthorized' => 'Não autorizado',
        'access_denied' => 'Acesso negado',
        'resource_not_found' => 'Recurso não encontrado',
        'internal_server_error' => 'Erro interno do servidor',
        'database_error' => 'Erro na base de dados',
    ],

    // Users messages
    'users' => [
        'list_retrieved' => 'Lista de usuários recuperada com sucesso',
        'user_created' => 'Usuário criado com sucesso',
        'user_updated' => 'Usuário atualizado com sucesso',
        'user_deleted' => 'Usuário excluído com sucesso',
        'user_retrieved' => 'Dados do usuário recuperados com sucesso',
        'user_not_found' => 'Usuário não encontrado',
        'search_completed' => 'Busca realizada com sucesso',
        'statistics_retrieved' => 'Estatísticas recuperadas com sucesso',
        'create_failed' => 'Falha ao criar usuário',
        'update_failed' => 'Falha ao atualizar usuário',
        'delete_failed' => 'Falha ao excluir usuário',
        'search_failed' => 'Falha na busca',
        'statistics_failed' => 'Falha ao recuperar estatísticas',
        'retrieve_failed' => 'Falha ao recuperar usuários',
    ],

    // Fields
    'fields' => [
        'name' => 'nome',
        'email' => 'email',
        'password' => 'senha',
        'role' => 'função',
        'roles' => 'funções',
    ],

    // Roles and Permissions
    'manager' => 'Gerente',
    'collaborator' => 'Colaborador',
    'permission_denied' => 'Você não tem permissão para acessar este recurso.',

    // Email messages
    'emails' => [
        'welcome_subject' => 'Bem-vindo(a) ao :app!',
        'welcome_title' => 'Olá, :name!',
        'welcome_message' => 'Seja bem-vindo(a) ao :company! Sua conta foi criada com sucesso e você já pode acessar o sistema.',
        'your_account_details' => 'Detalhes da sua conta',
        'name' => 'Nome',
        'email' => 'E-mail',
        'role' => 'Função',
        'manager' => 'Gerente',
        'collaborator' => 'Colaborador',
        'access_system' => 'Acessar Sistema',
        'welcome_footer' => 'Se você tiver alguma dúvida ou precisar de ajuda, não hesite em entrar em contato conosco.',
        'regards' => 'Atenciosamente',
    ],
];
