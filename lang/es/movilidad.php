<?php

return [
    // Sección general
    'titulo'              => 'Movilidad Isleña',
    'subtitulo'           => 'Lanchas y servicios fluviales del Delta',
    'muelles'             => 'Muelles',
    'muelle'              => 'Muelle',
    'servicios'           => 'Servicios',
    'servicio'            => 'Servicio',
    'zona'                => 'Zona',
    'rio'                 => 'Río / Canal',

    // Tipos de servicio
    'tipo_lancha_colectiva' => 'Lancha colectiva',
    'tipo_remise_fluvial'   => 'Remise fluvial',
    'tipo_carga'            => 'Carga',
    'tipo_especial'         => 'Servicio especial',

    // Avistajes — tipos
    'avistaje_paso'      => 'Pasó y paró',
    'avistaje_embarco'   => 'Embarcó',
    'avistaje_no_paro'   => 'Pasó sin parar',
    'avistaje_cancelado' => 'No vino / Cancelado',
    'avistaje_demorado'  => 'Viene demorado',

    // Avistajes — UI
    'avistaje_reportar'        => '¿Lo viste?',
    'avistaje_confirmar'       => '¿Vos también lo viste?',
    'avistaje_confirmar_accion'=> 'Confirmar',
    'avistaje_sin_reportes'    => 'Sin reportes de hoy',
    'avistaje_que_observaste'  => '¿Qué observaste?',
    'avistaje_sentido'         => 'Sentido',
    'avistaje_cuando'          => '¿Cuándo?',
    'avistaje_ahora'           => 'Ahora mismo',
    'avistaje_hace_minutos'    => 'Hace :count min',
    'avistaje_nota_opcional'   => 'Nota (opcional)',
    'avistaje_reportar_accion' => 'Reportar',
    'avistaje_cancelar'        => 'Cancelar',
    'avistaje_ultimo'          => 'Último reporte',
    'avistaje_confirmados_por' => '{1} lo confirmó :count persona|[2,*] lo confirmaron :count personas',

    // Frescura
    'freshness_ahora'        => 'Recién',
    'freshness_minutos'      => '{1} hace 1 min|[2,*] hace :count min',
    'freshness_hora'         => 'hace ~1 hora',
    'freshness_horas'        => 'hace ~:count horas',
    'freshness_viejo'        => 'Sin reportes recientes',
    'freshness_sin_datos'    => 'Sin datos',

    // Patrones (horario suave)
    'patron_habitual'           => 'Patrón habitual',
    'patron_todos_los_dias'     => 'Todos los días',
    'patron_hora_ventana'       => '~:hora (±:ventana min)',
    'patron_proximo_estimado'   => 'Próximo estimado',
    'patron_basado_en'          => 'Basado en patrón habitual',
    'patron_basado_en_avistaje' => 'Basado en último avistaje',
    'patron_sin_datos'          => 'Sin datos suficientes',

    // Sentidos
    'sentido_ida'           => 'Ida',
    'sentido_al_interior'   => 'Al interior',
    'sentido_a_tigre'       => '← A Tigre',
    'sentido_vuelta'        => 'Vuelta',
    'sentido_ambos'         => 'Ambos sentidos',
    'desde_tigre'         => 'Desde Tigre →',
    'salidas_desde_aca'     => 'Salidas',
    'reportando_la_de_las'  => 'Reportando la de las',

    // Días de la semana
    'domingo'   => 'Domingo',
    'lunes'     => 'Lunes',
    'martes'    => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves'    => 'Jueves',
    'viernes'   => 'Viernes',
    'sabado'    => 'Sábado',

    // Condiciones ambientales
    'condiciones_titulo'     => 'Condiciones actuales',
    'condiciones_ok'         => 'Navegación normal',
    'condiciones_precaucion' => 'Precaución',
    'condiciones_riesgo'     => 'Condiciones adversas',
    'condicion_marea_baja'   => 'Marea baja (:level m) — posibles restricciones en canales secundarios',
    'condicion_marea_alta'   => 'Marea alta (:level m) — condición favorable',
    'condicion_viento_fuerte'=> 'Viento fuerte (:speed km/h) — algunos servicios pueden demorar o cancelar',

    // Alertas
    'alerta_activa'          => 'Alerta activa',
    'alerta_suspension'      => 'Servicio suspendido',
    'alerta_demora_general'  => 'Demora general',
    'alerta_ruta_alternativa'=> 'Ruta alternativa',
    'alerta_hasta'           => 'Hasta :hora',
    'alerta_hasta_nuevo_aviso' => 'Hasta nuevo aviso',

    // Acciones y navegación
    'ver_historial'      => 'Ver historial',
    'ver_servicio'       => 'Ver servicio',
    'todos_los_muelles'  => 'Todos los muelles',
    'buscar_muelle'      => 'Buscar muelle...',
    'muelle_no_encontrado' => 'Muelle no encontrado',
    'volver'             => 'Volver',

    // Verificado
    'operador_verificado' => 'Operador verificado',
    'contacto'            => 'Contacto',

    // Reportar — contexto
    'reportar_sobre'      => 'Reportar sobre :servicio en :muelle',
    'login_para_reportar' => 'Iniciá sesión para reportar avistajes',

    // Confianza (confidence badge)
    'confidence_confirmado'    => 'Confirmado por la comunidad',
    'confidence_dudoso'        => 'Opiniones mixtas',
    'confidence_contradictorio'=> 'Mayoría reporta problemas',
    'confidence_oficial'       => 'Horario oficial',
    'confidence_comunidad'     => 'Dato comunitario',
    'confidence_estimado'      => 'Estimado — sin datos suficientes',

    // Descargo
    'disclaimer_horarios' => 'Los horarios son referencias comunitarias y pueden no coincidir con el servicio real. Para información oficial consultá directamente a Interisleña.',
];
