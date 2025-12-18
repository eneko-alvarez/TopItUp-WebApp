<?php
/**
 * Spanish Translations (Castellano de España)
 * Language: es (Spanish)
 */

return [
    // Authentication & Login
    'auth' => [
        'login' => [
            'title' => 'Iniciar Sesión',
            'username' => 'Usuario',
            'username_placeholder' => 'Introduce tu usuario',
            'password' => 'Contraseña',
            'password_placeholder' => 'Introduce tu contraseña',
            'submit' => 'Entrar',
        ],
        'register' => [
            'title' => 'Registrarse',
            'username' => 'Usuario',
            'username_placeholder' => 'Elige un nombre de usuario',
            'email' => 'Email',
            'email_placeholder' => 'Introduce tu email',
            'password' => 'Contraseña',
            'password_placeholder' => 'Crea una contraseña',
            'confirm_password' => 'Confirmar Contraseña',
            'confirm_password_placeholder' => 'Confirma tu contraseña',
            'submit' => 'Crear Cuenta',
            'username_check' => [
                'checking' => 'Comprobando...',
                'available' => 'Usuario disponible',
                'taken' => 'Usuario ya en uso',
                'min_length' => 'Mínimo 3 caracteres requeridos',
            ],
        ],
        'errors' => [
            'passwords_mismatch' => 'Las contraseñas no coinciden.',
            'password_min_length' => 'La contraseña debe tener al menos 8 caracteres.',
            'invalid_credentials' => 'Credenciales inválidas.',
            'username_exists' => 'El usuario o email ya existe.',
        ],
        'welcome_text' => 'Cuenta lo que te importa',
    ],

    // Landing Page (Installation)
    'landing' => [
        'subtitle' => 'Cuenta lo que importa.<br>Desde tu bolsillo.',
        'install_text' => 'Instalación más fácil que desde<br>la App Store o Google Play Store',
        'ios_button' => 'Para iOS',
        'android_button' => 'Para Android',
        'ios_instructions' => [
            'title' => 'Instalación iOS',
            'step1' => 'Pulsa el botón de <strong>Compartir</strong>.',
            'step2' => 'Desliza hacia abajo (o toca \'más\') y pulsa <strong>"Añadir a pantalla de inicio"</strong>.',
            'step3' => 'Pulsa <strong>Añadir</strong> arriba a la derecha.',
            'success' => '¡Has instalado TopItUp con éxito! ¡Ábrelo y empieza a usarlo!',
        ],
        'android_instructions' => [
            'title' => 'Instalación Android',
            'step1' => 'Pulsa el icono de <strong>Menú</strong> (tres puntos).',
            'step2' => 'Pulsa <strong>"Instalar app"</strong> o <strong>"Añadir a pantalla de inicio"</strong>.',
            'step3' => 'Sigue las instrucciones para instalar.',
            'success' => '¡Has instalado TopItUp con éxito! ¡Ábrelo y empieza a usarlo!',
        ],
    ],

    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'leaderboard' => 'Leaderboard',
        'settings' => 'Settings',
        'logout' => 'Salir',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'TopItUp Dashboard',
        'subtitle' => '2026 WRAP :)',
        'empty' => [
            'title' => 'No tienes grupos ni contadores',
            'message' => 'Ve a Settings para crear tu primer grupo o contador.',
        ],
        'history' => [
            'title' => 'Historial (Últimos 10)',
            'empty' => 'No hay actividad reciente.',
        ],
        'no_counters_assigned' => 'Sin contadores asignados',
    ],

    // Settings
    'settings' => [
        'create_counter' => [
            'title' => 'Crear Nuevo Contador',
            'name' => 'Nombre del Contador',
            'name_placeholder' => 'Ej: Cervezas, Gym',
            'color' => 'Color',
            'submit' => 'Crear Contador',
        ],
        'create_group' => [
            'title' => 'Crear Nuevo Grupo',
            'name' => 'Nombre del Grupo',
            'name_placeholder' => 'Ej: Bebidas',
            'color' => 'Color',
            'submit' => 'Crear Grupo',
        ],
        'my_groups' => [
            'title' => 'Mis Grupos',
            'empty' => 'Aún no hay grupos',
            'no_counters' => 'Sin contadores',
            'add_counters' => 'Añadir contadores',
            'total' => 'Total',
            'delete' => 'Eliminar',
        ],
        'my_counters' => [
            'title' => 'Mis Contadores',
            'empty' => 'Aún no hay contadores',
            'count' => 'Cuenta',
            'view' => 'Ver',
            'unassigned' => 'Sin asignar',
        ],
        'errors' => [
            'counter_exists' => 'Ya existe un contador con ese nombre.',
            'group_exists' => 'Ya existe un grupo con ese nombre.',
            'create_failed' => 'Error al crear. Inténtalo de nuevo.',
        ],
        'success' => [
            'counter_created' => '¡Contador creado con éxito!',
            'group_created' => '¡Grupo creado con éxito!',
            'counter_deleted' => '¡Contador eliminado con éxito!',
            'group_deleted' => '¡Grupo eliminado con éxito!',
        ],
    ],

    // Increment Page
    'increment' => [
        'add_one' => '+1',
        'history' => 'Historial',
        'empty_history' => 'Sin registros aún',
        'delete_log' => 'Eliminar',
        'confirm_delete_log' => '¿Eliminar esta entrada?',
    ],

    // Leaderboard
    'leaderboard' => [
        'join' => [
            'title' => 'Unirse a Leaderboard',
            'code' => 'Código de Invitación',
            'code_placeholder' => 'Introduce el código',
            'submit' => 'Unirse',
        ],
        'create' => [
            'title' => 'Crear Nuevo Leaderboard',
            'name' => 'Nombre del Leaderboard',
            'name_placeholder' => 'Ej: Reto de Cervezas',
            'track_type' => 'Rastrear',
            'track_counter' => 'Contador Individual',
            'track_group' => 'Todos los Contadores del Grupo',
            'select_counter' => 'Selecciona Contador',
            'select_group' => 'Selecciona Grupo',
            'submit' => 'Crear Leaderboard',
        ],
        'my_leaderboards' => 'Mis Leaderboards',
        'empty' => 'Aún no hay leaderboards',
        'members' => 'miembros',
        'view' => [
            'rankings' => 'Clasificación',
            'rank' => 'Posición',
            'member' => 'Miembro',
            'count' => 'Cuenta',
            'settings' => 'Ajustes',
            'last_updated' => 'Última actualización',
            'empty' => 'Aún no hay miembros. ¡Comparte el código de invitación para que se unan!',
        ],
        'settings' => [
            'title' => 'Ajustes del Leaderboard',
            'members' => 'Miembros',
            'no_members' => 'Aún no hay miembros',
            'invite_code' => 'Código de Invitación',
            'invitation_code' => 'Código de Invitación',
            'copy' => 'Copiar',
            'copied' => '¡Copiado!',
            'remove_member' => 'Eliminar',
            'leave_leaderboard' => 'Abandonar Leaderboard',
            'delete_leaderboard' => 'Eliminar Leaderboard',
            'confirm_leave' => '¿Seguro que quieres abandonar este leaderboard?',
            'confirm_delete' => '¿Seguro? Esto eliminará el leaderboard para todos.',
            'danger_zone' => 'Zona de Peligro',
            'leave' => 'Salir del Leaderboard',
            'leave_message' => 'Salir del leaderboard te quitará del ranking. Necesitarás un nuevo código para volver a unirte.',
            'change_tracking' => 'Cambiar lo que Rastrear',
            'single_counter' => 'Contador Individual',
            'counter_group' => 'Grupo de Contadores',
            'select_counter' => 'Seleccionar Contador',
            'select_group' => 'Seleccionar Grupo',
            'timespan' => [
                'title' => 'Filtro de Periodo',
                'description' => 'Solo los registros dentro de este rango de fechas contarán para el ranking.',
                'start' => 'Fecha de Inicio',
                'end' => 'Fecha de Fin',
                'submit' => 'Actualizar Periodo',
            ],
        ],
        'errors' => [
            'invalid_code' => 'Código de invitación inválido.',
            'already_member' => 'Ya eres miembro.',
            'create_failed' => 'Error al crear el leaderboard.',
            'select_item' => 'Por favor selecciona un contador o grupo.',
        ],
        'success' => [
            'joined' => '¡Te has unido al leaderboard con éxito!',
            'created' => '¡Leaderboard creado con éxito!',
            'left' => 'Has abandonado el leaderboard.',
            'deleted' => 'Leaderboard eliminado con éxito.',
        ],
    ],

    // Common
    'common' => [
        'page_not_found' => 'Página no encontrada.',
        'error' => 'Error',
        'success' => 'Éxito',
        'delete' => 'Eliminar',
        'cancel' => 'Cancelar',
        'confirm' => 'Confirmar',
        'save' => 'Guardar',
        'edit' => 'Editar',
        'close' => 'Cerrar',
        'loading' => 'Cargando...',
        'user' => 'Usuario',
        'copy' => 'Copiar',
        'track' => 'Rastrear',
        'select' => 'Seleccionar',
    ],

    // Meta Tags & SEO
    'meta' => [
        'title' => 'TopItUp 🎉 ¡Cuenta de Todo!',
        'description' => '¿Quién dijo que contar no puede ser divertido? Cuenta tus cervezas, cafés, entrenamientos, o literalmente cualquier cosa. Compite con amigos y mira quién está ganando en la vida 🏆',
        'keywords' => 'app contador, rastreador de hábitos, rastreador de actividades, leaderboard, contar bebidas, rastrear hábitos, pwa, productividad',
        'og_title' => 'TopItUp - Cuenta lo que Importa',
        'og_description' => '¿Cervezas? ¿Sesiones de gym? ¿Días sin uni? Cuenta lo que quieras, compite con colegas y diviértete haciéndolo. ¡Es una fiesta! 🎊',
    ],

    // Language Switcher
    'language' => [
        'name' => 'Español',
        'code' => 'es',
        'switch_to' => 'Cambiar a inglés',
    ],
];
