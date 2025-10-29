<?php

return [
    // Auth messages
    'auth' => [
        'login_success' => 'Login successful',
        'login_failed' => 'Login failed',
        'logout_success' => 'Logout successful',
        'logout_failed' => 'Logout failed',
        'user_data_retrieved' => 'User data retrieved successfully',
        'invalid_credentials' => 'The provided credentials are incorrect.',
        'access_denied_managers_only' => 'Only managers can login.',
        'token_invalid' => 'Invalid or expired access token.',
        'access_denied_managers_resource' => 'Access denied. Only managers can access this resource.',
        'access_denied_unauthenticated' => 'Access denied. You need to be authenticated.',
        'access_denied_insufficient_privileges' => 'Access denied. Insufficient privileges.',
    ],

    // Validation messages
    'validation' => [
        'email_required' => 'The email field is required.',
        'email_format' => 'The email must be a valid email address.',
        'email_max' => 'The email may not be greater than :max characters.',
        'email_unique' => 'This email is already taken.',
        'password_required' => 'The password field is required.',
        'password_string' => 'The password must be a string.',
        'password_min' => 'The password must be at least :min characters.',
        'password_max' => 'The password may not be greater than :max characters.',
        'name_required' => 'The name field is required.',
        'name_string' => 'The name must be a string.',
        'name_max' => 'The name may not be greater than :max characters.',
        'role_required' => 'The role field is required.',
        'role_in' => 'The role must be manager or collaborator.',
        'roles_required' => 'The roles field is required.',        'roles_array' => 'The roles must be an array.',        'roles_invalid' => 'One or more roles are invalid.',        'password_confirmed' => 'The password confirmation does not match.',
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
        'success' => 'Operation completed successfully',
        'error' => 'Operation error',
        'created' => 'Created successfully',
        'updated' => 'Updated successfully',
        'deleted' => 'Deleted successfully',
        'retrieved' => 'Data retrieved successfully',
        'unauthorized' => 'Unauthorized',
        'access_denied' => 'Access denied',
        'resource_not_found' => 'Resource not found',
        'internal_server_error' => 'Internal server error',
        'database_error' => 'Database error',
    ],

    // Users messages
    'users' => [
        'list_retrieved' => 'User list retrieved successfully',
        'user_created' => 'User created successfully',
        'user_updated' => 'User updated successfully',
        'user_deleted' => 'User deleted successfully',
        'user_retrieved' => 'User data retrieved successfully',
        'user_not_found' => 'User not found',
        'search_completed' => 'Search completed successfully',
        'statistics_retrieved' => 'Statistics retrieved successfully',
        'create_failed' => 'Failed to create user',
        'update_failed' => 'Failed to update user',
        'delete_failed' => 'Failed to delete user',
        'search_failed' => 'Search failed',
        'statistics_failed' => 'Failed to retrieve statistics',
        'retrieve_failed' => 'Failed to retrieve users',
    ],

    // Fields
    'fields' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'role' => 'role',
        'roles' => 'roles',
    ],

    // Roles and Permissions
    'manager' => 'Manager',
    'collaborator' => 'Collaborator',
    'permission_denied' => 'You do not have permission to access this resource.',

    // Email messages
    'emails' => [
        'welcome_subject' => 'Welcome to :app!',
        'welcome_title' => 'Hello, :name!',
        'welcome_message' => 'Welcome to :company! Your account has been successfully created and you can now access the system.',
        'your_account_details' => 'Your account details',
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'manager' => 'Manager',
        'collaborator' => 'Collaborator',
        'access_system' => 'Access System',
        'welcome_footer' => 'If you have any questions or need help, please do not hesitate to contact us.',
        'regards' => 'Best regards',
    ],
];
