<?php

return [
    // General
    'titulo'              => 'Island Mobility',
    'subtitulo'           => 'Boats and river services in the Delta',
    'muelles'             => 'Docks',
    'muelle'              => 'Dock',
    'servicios'           => 'Services',
    'servicio'            => 'Service',
    'zona'                => 'Zone',
    'rio'                 => 'River / Canal',

    // Service types
    'tipo_lancha_colectiva' => 'Collective boat',
    'tipo_remise_fluvial'   => 'River taxi',
    'tipo_carga'            => 'Cargo',
    'tipo_especial'         => 'Special service',

    // Sightings — types
    'avistaje_paso'      => 'Stopped here',
    'avistaje_embarco'   => 'Boarded',
    'avistaje_no_paro'   => 'Passed without stopping',
    'avistaje_cancelado' => 'Did not come / Cancelled',
    'avistaje_demorado'  => 'Running late',

    // Sightings — UI
    'avistaje_reportar'        => 'Did you see it?',
    'avistaje_confirmar'       => 'Did you see it too?',
    'avistaje_confirmar_accion'=> 'Confirm',
    'avistaje_sin_reportes'    => 'No reports today',
    'avistaje_que_observaste'  => 'What did you observe?',
    'avistaje_sentido'         => 'Direction',
    'avistaje_cuando'          => 'When?',
    'avistaje_ahora'           => 'Just now',
    'avistaje_hace_minutos'    => ':count min ago',
    'avistaje_nota_opcional'   => 'Note (optional)',
    'avistaje_reportar_accion' => 'Report',
    'avistaje_cancelar'        => 'Cancel',
    'avistaje_ultimo'          => 'Last report',
    'avistaje_confirmados_por' => '{1} :count person confirmed this|[2,*] :count people confirmed this',

    // Freshness
    'freshness_ahora'        => 'Just now',
    'freshness_minutos'      => '{1} 1 min ago|[2,*] :count min ago',
    'freshness_hora'         => '~1 hour ago',
    'freshness_horas'        => '~:count hours ago',
    'freshness_viejo'        => 'No recent reports',
    'freshness_sin_datos'    => 'No data',

    // Patterns (soft schedule)
    'patron_habitual'           => 'Usual pattern',
    'patron_todos_los_dias'     => 'Every day',
    'patron_hora_ventana'       => '~:hora (±:ventana min)',
    'patron_proximo_estimado'   => 'Next estimated',
    'patron_basado_en'          => 'Based on usual pattern',
    'patron_basado_en_avistaje' => 'Based on last sighting',
    'patron_sin_datos'          => 'Not enough data',

    // Directions
    'sentido_ida'           => 'Outbound',
    'sentido_al_interior'   => 'Into the delta',
    'sentido_a_tigre'       => '← To Tigre',
    'sentido_vuelta'        => 'Return',
    'sentido_ambos'         => 'Both directions',
    'desde_tigre'         => 'Departs Tigre →',
    'salidas_desde_aca'     => 'Departures',
    'reportando_la_de_las'  => 'Reporting sighting for',

    // Days
    'domingo'   => 'Sunday',
    'lunes'     => 'Monday',
    'martes'    => 'Tuesday',
    'miercoles' => 'Wednesday',
    'jueves'    => 'Thursday',
    'viernes'   => 'Friday',
    'sabado'    => 'Saturday',

    // Environmental conditions
    'condiciones_titulo'     => 'Current conditions',
    'condiciones_ok'         => 'Normal navigation',
    'condiciones_precaucion' => 'Caution',
    'condiciones_riesgo'     => 'Adverse conditions',
    'condicion_marea_baja'   => 'Low tide (:level m) — possible restrictions on secondary channels',
    'condicion_marea_alta'   => 'High tide (:level m) — favourable conditions',
    'condicion_viento_fuerte'=> 'Strong wind (:speed km/h) — some services may be delayed or cancelled',

    // Alerts
    'alerta_activa'          => 'Active alert',
    'alerta_suspension'      => 'Service suspended',
    'alerta_demora_general'  => 'General delay',
    'alerta_ruta_alternativa'=> 'Alternative route',
    'alerta_hasta'           => 'Until :hora',
    'alerta_hasta_nuevo_aviso' => 'Until further notice',

    // Actions & navigation
    'ver_historial'      => 'View history',
    'ver_servicio'       => 'View service',
    'todos_los_muelles'  => 'All docks',
    'buscar_muelle'      => 'Search dock...',
    'muelle_no_encontrado' => 'Dock not found',
    'volver'             => 'Back',

    // Verified
    'operador_verificado' => 'Verified operator',
    'contacto'            => 'Contact',

    // Report context
    'reportar_sobre'      => 'Report on :servicio at :muelle',
    'login_para_reportar' => 'Log in to report sightings',

    // Descargo
    'disclaimer_horarios' => 'Schedules are community references and may not match actual service. For official information contact Interisleña directly.',
];
