<?php

return [
    // Général
    'titulo'              => 'Mobilité Insulaire',
    'subtitulo'           => 'Bateaux et services fluviaux du Delta',
    'muelles'             => 'Embarcadères',
    'muelle'              => 'Embarcadère',
    'servicios'           => 'Services',
    'servicio'            => 'Service',
    'zona'                => 'Zone',
    'rio'                 => 'Rivière / Canal',

    // Types de service
    'tipo_lancha_colectiva' => 'Navette collective',
    'tipo_remise_fluvial'   => 'Taxi fluvial',
    'tipo_carga'            => 'Fret',
    'tipo_especial'         => 'Service spécial',

    // Observations — types
    'avistaje_paso'             => 'Arrêté ici',
    'avistaje_embarco'          => 'Embarqué',
    'avistaje_no_paro'          => 'Passé sans s\'arrêter',
    'avistaje_cancelado'        => 'Non venu / Annulé',
    'avistaje_demorado'         => 'En retard',
    'avistaje_problema_muelle'  => 'Problème à l\'embarcadère',
    'avistaje_otro'             => 'Autre situation',

    // Observations — UI
    'avistaje_reportar'        => 'Vous l\'avez vu ?',
    'avistaje_confirmar'       => 'Vous aussi vous l\'avez vu ?',
    'avistaje_confirmar_accion'=> 'Confirmer',
    'avistaje_sin_reportes'    => 'Aucun signalement aujourd\'hui',
    'avistaje_que_observaste'  => 'Qu\'avez-vous observé ?',
    'avistaje_sentido'         => 'Direction',
    'avistaje_cuando'          => 'Quand ?',
    'avistaje_ahora'           => 'À l\'instant',
    'avistaje_hace_minutos'    => 'il y a :count min',
    'avistaje_nota_opcional'   => 'Note (facultatif)',
    'avistaje_reportar_accion' => 'Signaler',
    'avistaje_cancelar'        => 'Annuler',
    'avistaje_ultimo'          => 'Dernier signalement',
    'avistaje_confirmados_por' => '{1} :count personne a confirmé|[2,*] :count personnes ont confirmé',

    // Fraîcheur
    'freshness_ahora'        => 'À l\'instant',
    'freshness_minutos'      => '{1} il y a 1 min|[2,*] il y a :count min',
    'freshness_hora'         => 'il y a ~1 heure',
    'freshness_horas'        => 'il y a ~:count heures',
    'freshness_viejo'        => 'Aucun signalement récent',
    'freshness_sin_datos'    => 'Pas de données',

    // Horaires souples
    'patron_habitual'           => 'Habitudes de passage',
    'patron_todos_los_dias'     => 'Tous les jours',
    'patron_hora_ventana'       => '~:hora (±:ventana min)',
    'patron_proximo_estimado'   => 'Prochain estimé',
    'patron_basado_en'          => 'Basé sur les habitudes',
    'patron_basado_en_avistaje' => 'Basé sur le dernier signalement',
    'patron_sin_datos'          => 'Données insuffisantes',

    // Directions
    'sentido_ida'           => 'Aller',
    'sentido_al_interior'   => 'Vers l\'intérieur',
    'sentido_a_tigre'       => '← Vers Tigre',
    'sentido_vuelta'        => 'Retour',
    'sentido_ambos'         => 'Les deux sens',
    'desde_tigre'         => 'Départ de Tigre →',
    'salidas_desde_aca'     => 'Départs',
    'reportando_la_de_las'  => 'Signalement pour',

    // Jours
    'domingo'   => 'Dimanche',
    'lunes'     => 'Lundi',
    'martes'    => 'Mardi',
    'miercoles' => 'Mercredi',
    'jueves'    => 'Jeudi',
    'viernes'   => 'Vendredi',
    'sabado'    => 'Samedi',

    // Conditions environnementales
    'condiciones_titulo'     => 'Conditions actuelles',
    'condiciones_ok'         => 'Navigation normale',
    'condiciones_precaucion' => 'Prudence',
    'condiciones_riesgo'     => 'Conditions défavorables',
    'condicion_marea_baja'   => 'Marée basse (:level m) — restrictions possibles sur les canaux secondaires',
    'condicion_marea_alta'   => 'Marée haute (:level m) — conditions favorables',
    'condicion_viento_fuerte'=> 'Vent fort (:speed km/h) — certains services peuvent être retardés ou annulés',

    // Alertes
    'alerta_activa'          => 'Alerte active',
    'alerta_suspension'      => 'Service suspendu',
    'alerta_demora_general'  => 'Retard général',
    'alerta_ruta_alternativa'=> 'Itinéraire alternatif',
    'alerta_hasta'           => 'Jusqu\'à :hora',
    'alerta_hasta_nuevo_aviso' => 'Jusqu\'à nouvel ordre',

    // Actions et navigation
    'ver_historial'      => 'Voir l\'historique',
    'ver_servicio'       => 'Voir le service',
    'todos_los_muelles'  => 'Tous les embarcadères',
    'buscar_muelle'      => 'Rechercher un embarcadère...',
    'muelle_no_encontrado' => 'Embarcadère introuvable',
    'volver'             => 'Retour',

    // Vérifié
    'operador_verificado' => 'Opérateur vérifié',
    'contacto'            => 'Contact',

    // Signalement
    'reportar_sobre'      => 'Signaler sur :servicio à :muelle',
    'login_para_reportar' => 'Connectez-vous pour signaler des observations',

    // Horaires personnels
    'horarios_titulo'        => "Horaires d'aujourd'hui",
    'salidas_hoy'            => "Départs d'aujourd'hui",
    'salidas_hacia_tigre'    => 'Vers Tigre',
    'salidas_desde_tigre'    => 'Depuis Tigre',
    'llega_muelle_aprox'     => 'arrive ~:hora',
    'sale_tigre_hora'        => 'départ Tigre :hora',
    'sin_horarios_vuelta'    => "Aucun horaire de retour enregistré pour aujourd'hui.",
    'manana_label'           => 'Demain',
    'ya_salio'               => 'déjà parti',
    'salida_en_min'          => 'dans :min min',
    'salida_en_horas'        => 'dans :h h :m min',
    'confirmaron_n'          => '{1} 1 confirmé|[2,*] :count confirmés',
    'confirmaron_demora'     => '{1} 1 a confirmé le retard|[2,*] :count ont confirmé le retard',
    'sin_confirmaciones'     => 'Pas encore de confirmation',
    'reportar_algo'          => 'signaler quelque chose',
    'ya_lo_vi'               => 'compris',
    'aviso_activo_label'     => 'Alerte active',
    'tolerancia_label'       => '± :min min',
    'proxima_label'          => 'prochain',
    'sin_muelle_elegido'     => 'Choisissez votre embarcadère pour voir vos horaires',
    'confirmar_salida'       => 'Confirmer',
    'que_esta_pasando'       => 'Que se passe-t-il ?',

    // Visibilité et regroupement des départs
    'schedule_past_toggle_show' => ':count précédents',
    'schedule_past_toggle_hide' => 'masquer les précédents',
    'schedule_recent_hint'      => 'Est-il passé ?',
    'schedule_recent_ago'       => 'il y a :time',

    // Panneau de signalement
    'sighting_panel_title'             => 'Que se passe-t-il ?',
    'sighting_panel_subtitle'          => "Votre signalement aide tous ceux qui partent de cet embarcadère.",
    'sighting_note_toggle_open'        => '+ Ajouter un détail',
    'sighting_note_toggle_close'       => '− Retirer le détail',
    'sighting_note_placeholder'        => 'Précisez si vous le souhaitez...',
    'sighting_submit_success_title'    => "Merci pour l'info",
    'sighting_submit_success_subtitle' => 'Votre signalement :type est maintenant visible par tous.',
    'sighting_btn_send'                => 'Envoyer le signalement',
    'sighting_btn_cancel'              => 'Annuler',

    // Réactions sur les cartes de départ
    'departure_reaction_negative_title'    => 'Quelque chose ne va pas ?',
    'departure_reaction_negative_subtitle' => 'Connectez-vous pour signaler un retard ou un problème.',
    'departure_reaction_login_cta'         => 'Signaler',
    'departure_reaction_dismiss_cta'       => 'Tout va bien',
    'departure_sighting_strip'             => ':type · :name · :time',

    // Descargo
    'disclaimer_horarios' => 'Les horaires sont des références communautaires et peuvent ne pas correspondre au service réel. Pour des informations officielles, contactez directement Interisleña.',
];
