<?php

return [
    // Sección comunitaria
    'community_section_label' => 'Horarios comunitarios',
    'official_section_label'  => 'Horarios verificados',

    // Estado de confianza
    'trust_pending'           => 'por confirmar',
    'trust_verified'          => 'confirmado por la comunidad',
    'trust_progress'          => ':count de 5 confirmaciones',
    'trust_author'            => 'por :name',

    // Recurrencia
    'recurrencia_diario'      => 'Todos los días',
    'recurrencia_lv'          => 'Lunes a viernes',
    'recurrencia_sabado'      => 'Sábado',
    'recurrencia_domingo'     => 'Domingo / Feriados',
    'recurrencia_fds'         => 'Fines de semana',
    'recurrencia_unico'       => 'Una vez',

    // Formulario de creación
    'create_toggle_label'     => 'Agregar muelle o horario',
    'create_toggle_sub'       => 'Lo que agregues queda visible y se confirma con la comunidad',
    'create_tab_schedule'     => 'Horario',
    'create_tab_dock'         => 'Muelle nuevo',
    'create_login_prompt'     => 'Iniciá sesión para agregar un horario o muelle',

    // Campos horario
    'field_hora'              => 'Hora de salida',
    'field_empresa'           => 'Empresa / nombre (opcional)',
    'field_desde'             => 'Desde',
    'field_hacia'             => 'Hacia',
    'field_dias'              => 'Días',
    'field_tolerancia'        => 'Tolerancia',
    'field_sentido'           => 'Sentido',

    // Campos muelle
    'field_muelle_nombre'     => 'Nombre del muelle',
    'field_muelle_zona'       => 'Zona / río (opcional)',
    'field_muelle_referencia' => 'Referencia cercana (opcional)',
    'field_muelle_ref_hint'   => 'Ej: frente a la casa naranja, km 42 del canal',

    // Placeholders
    'placeholder_hacia'       => 'Ej: Tigre, Delta Bajo',
    'placeholder_empresa'     => 'Ej: Interisleña, Don Carlos',
    'placeholder_nombre'      => 'Ej: Muelle km 42',
    'placeholder_zona'        => 'Ej: Canal Caraguatá',
    'placeholder_referencia'  => 'Ej: Casa verde con mástil',

    // Acciones
    'btn_submit_schedule'     => 'Agregar horario',
    'btn_submit_dock'         => 'Proponer muelle',
    'btn_cancel'              => 'Cancelar',

    // Mensajes de éxito
    'success_schedule'        => 'Horario agregado. Aparecerá cuando 5 personas lo confirmen.',
    'success_dock'            => 'Muelle propuesto. Aparecerá disponible cuando 5 personas lo confirmen.',

    // Errores
    'error_dock_required'     => 'Seleccioná un muelle de salida.',

    // Sentidos
    'sentido_vuelta'          => 'Hacia Tigre',
    'sentido_ida'             => 'Desde Tigre',

    // Tolerancia opciones
    'tolerancia_15'           => '± 15 min',
    'tolerancia_20'           => '± 20 min',
    'tolerancia_30'           => '± 30 min',
];
