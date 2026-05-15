<?php

return [
    // Geral
    'titulo'              => 'Mobilidade Insular',
    'subtitulo'           => 'Barcos e serviços fluviais do Delta',
    'muelles'             => 'Atracadouros',
    'muelle'              => 'Atracadouro',
    'servicios'           => 'Serviços',
    'servicio'            => 'Serviço',
    'zona'                => 'Zona',
    'rio'                 => 'Rio / Canal',

    // Tipos de serviço
    'tipo_lancha_colectiva' => 'Lancha coletiva',
    'tipo_remise_fluvial'   => 'Táxi fluvial',
    'tipo_carga'            => 'Carga',
    'tipo_especial'         => 'Serviço especial',

    // Avistamentos — tipos
    'avistaje_paso'      => 'Parou aqui',
    'avistaje_embarco'   => 'Embarcou',
    'avistaje_no_paro'   => 'Passou sem parar',
    'avistaje_cancelado' => 'Não veio / Cancelado',
    'avistaje_demorado'  => 'Atrasado',

    // Avistamentos — UI
    'avistaje_reportar'        => 'Você viu?',
    'avistaje_confirmar'       => 'Você também viu?',
    'avistaje_confirmar_accion'=> 'Confirmar',
    'avistaje_sin_reportes'    => 'Sem relatos hoje',
    'avistaje_que_observaste'  => 'O que você observou?',
    'avistaje_sentido'         => 'Sentido',
    'avistaje_cuando'          => 'Quando?',
    'avistaje_ahora'           => 'Agora mesmo',
    'avistaje_hace_minutos'    => 'há :count min',
    'avistaje_nota_opcional'   => 'Observação (opcional)',
    'avistaje_reportar_accion' => 'Relatar',
    'avistaje_cancelar'        => 'Cancelar',
    'avistaje_ultimo'          => 'Último relato',
    'avistaje_confirmados_por' => '{1} :count pessoa confirmou|[2,*] :count pessoas confirmaram',

    // Frescor
    'freshness_ahora'        => 'Agora',
    'freshness_minutos'      => '{1} há 1 min|[2,*] há :count min',
    'freshness_hora'         => 'há ~1 hora',
    'freshness_horas'        => 'há ~:count horas',
    'freshness_viejo'        => 'Sem relatos recentes',
    'freshness_sin_datos'    => 'Sem dados',

    // Padrões (horário suave)
    'patron_habitual'           => 'Padrão habitual',
    'patron_todos_los_dias'     => 'Todos os dias',
    'patron_hora_ventana'       => '~:hora (±:ventana min)',
    'patron_proximo_estimado'   => 'Próximo estimado',
    'patron_basado_en'          => 'Baseado no padrão habitual',
    'patron_basado_en_avistaje' => 'Baseado no último relato',
    'patron_sin_datos'          => 'Dados insuficientes',

    // Sentidos
    'sentido_ida'           => 'Ida',
    'sentido_al_interior'   => 'Para o interior',
    'sentido_a_tigre'       => '← Para Tigre',
    'sentido_vuelta'        => 'Volta',
    'sentido_ambos'         => 'Ambos os sentidos',
    'desde_tigre'         => 'Sai de Tigre →',
    'salidas_desde_aca'     => 'Saídas',
    'reportando_la_de_las'  => 'Reportando a de',

    // Dias
    'domingo'   => 'Domingo',
    'lunes'     => 'Segunda-feira',
    'martes'    => 'Terça-feira',
    'miercoles' => 'Quarta-feira',
    'jueves'    => 'Quinta-feira',
    'viernes'   => 'Sexta-feira',
    'sabado'    => 'Sábado',

    // Condições ambientais
    'condiciones_titulo'     => 'Condições atuais',
    'condiciones_ok'         => 'Navegação normal',
    'condiciones_precaucion' => 'Atenção',
    'condiciones_riesgo'     => 'Condições adversas',
    'condicion_marea_baja'   => 'Maré baixa (:level m) — possíveis restrições em canais secundários',
    'condicion_marea_alta'   => 'Maré alta (:level m) — condições favoráveis',
    'condicion_viento_fuerte'=> 'Vento forte (:speed km/h) — alguns serviços podem atrasar ou cancelar',

    // Alertas
    'alerta_activa'          => 'Alerta ativa',
    'alerta_suspension'      => 'Serviço suspenso',
    'alerta_demora_general'  => 'Atraso geral',
    'alerta_ruta_alternativa'=> 'Rota alternativa',
    'alerta_hasta'           => 'Até :hora',
    'alerta_hasta_nuevo_aviso' => 'Até novo aviso',

    // Ações e navegação
    'ver_historial'      => 'Ver histórico',
    'ver_servicio'       => 'Ver serviço',
    'todos_los_muelles'  => 'Todos os atracadouros',
    'buscar_muelle'      => 'Buscar atracadouro...',
    'muelle_no_encontrado' => 'Atracadouro não encontrado',
    'volver'             => 'Voltar',

    // Verificado
    'operador_verificado' => 'Operador verificado',
    'contacto'            => 'Contato',

    // Relatar contexto
    'reportar_sobre'      => 'Relatar sobre :servicio em :muelle',
    'login_para_reportar' => 'Entre para relatar avistamentos',

    // Descargo
    'disclaimer_horarios' => 'Os horários são referências comunitárias e podem não corresponder ao serviço real. Para informações oficiais consulte diretamente a Interisleña.',
];
