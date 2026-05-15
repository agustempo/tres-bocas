<?php

namespace Database\Seeders;

use App\Models\Muelle;
use App\Models\Patron;
use App\Models\Servicio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovilidadSeeder extends Seeder
{
    public function run(): void
    {
        // ── Limpiar datos previos (los muelles se preservan con updateOrCreate) ──
        Patron::query()->delete();
        DB::table('muelle_servicio')->truncate();
        Servicio::query()->delete();

        // ── Servicio ─────────────────────────────────────────────────────────────
        $svc = Servicio::create([
            'slug'        => 'interislena',
            'nombre'      => 'Interisleña',
            'operador'    => 'Interisleña',
            'tipo'        => 'lancha_colectiva',
            'descripcion' => 'Servicio troncal por Río Sarmiento y Río Capitán. Tigre → Paraná.',
            'activo'      => true,
            'verificado'  => false,
        ]);

        // ── Muelles ──────────────────────────────────────────────────────────────
        //
        // orden define el orden de aparición en la lista pública.
        // Troncal Sarmiento:          1–11
        // Troncal Capitán (Felipe→Paraná): 12–16
        // Terminales Capitán (stubs): 20–28
        // Ramal Abra Vieja:           40–44
        // Ramal San Antonio→Borasso:  50–57
        //
        $muellesData = [
            // ── Troncal Sarmiento ──────────────────────────────────────────────
            [
                'slug' => 'tigre', 'orden' => 1,
                'nombre' => 'Tigre', 'rio' => 'Río Tigre', 'zona' => 'Tigre',
                'tipo_canal' => 'rio_principal',
                'descripcion' => 'Terminal fluvial de Tigre. Punto de partida de todos los servicios.',
                'latitud' => -34.4259, 'longitud' => -58.5789,
            ],
            ['slug' => 'tamarindos',          'orden' => 2,  'nombre' => 'Tamarindos',           'zona' => 'Primera Sección'],
            ['slug' => 'gambado',              'orden' => 3,  'nombre' => 'Gambado',               'zona' => 'Primera Sección'],
            ['slug' => 'raquel-ii',            'orden' => 4,  'nombre' => 'Raquel II',             'zona' => 'Primera Sección'],
            ['slug' => 'curubica',             'orden' => 5,  'nombre' => 'Curubica',              'zona' => 'Primera Sección'],
            [
                'slug' => 'museo', 'orden' => 6,
                'nombre' => 'Museo', 'zona' => 'Primera Sección',
                'descripcion' => 'Punto de empalme con el Arroyo Abra Vieja (ramal Espera / Cruz Colorada).',
            ],
            ['slug' => 'saucelandia',          'orden' => 7,  'nombre' => 'Saucelandia',          'zona' => 'Primera Sección'],
            [
                'slug' => 'tres-bocas', 'orden' => 8,
                'nombre' => 'Tres Bocas', 'zona' => 'Primera Sección',
                'descripcion' => 'Confluencia de tres arroyos. Parada clave del camino.',
            ],
            ['slug' => 'esmeralda',            'orden' => 9,  'nombre' => 'Esmeralda',            'zona' => 'Primera Sección'],
            ['slug' => 'villa-graciela',       'orden' => 10, 'nombre' => 'Villa Graciela',       'zona' => 'Primera Sección'],
            [
                'slug' => 'publico-san-antonio', 'orden' => 11,
                'nombre' => 'Público San Antonio', 'zona' => 'Primera Sección',
                'descripcion' => 'Empalme entre el Río Sarmiento y el Río Capitán.',
            ],
            // ── Troncal Capitán (Felipe → Paraná) ──────────────────────────────
            ['slug' => 'felipe',               'orden' => 12, 'nombre' => 'Felipe',               'rio' => 'Río Capitán',       'zona' => 'Segunda Sección'],
            ['slug' => 'santa-fe',             'orden' => 13, 'nombre' => 'Santa Fe',             'rio' => 'Río Capitán',       'zona' => 'Segunda Sección'],
            [
                'slug' => 'boca-rama-negra', 'orden' => 14,
                'nombre' => 'Boca Rama Negra', 'rio' => 'Arroyo Rama Negra', 'zona' => 'Segunda Sección',
                'descripcion' => 'Punto de embarco alternativo cuando la bajante impide llegar a Rama Negra.',
            ],
            [
                'slug' => 'rama-negra', 'orden' => 15,
                'nombre' => 'Rama Negra', 'rio' => 'Arroyo Rama Negra', 'zona' => 'Segunda Sección',
                'descripcion' => 'Muelle sobre el Arroyo Rama Negra. Con bajante extrema la lancha termina en Boca Rama Negra.',
            ],
            [
                'slug' => 'parana', 'orden' => 16,
                'nombre' => 'Paraná', 'rio' => 'Río Capitán', 'zona' => 'Segunda Sección',
                'descripcion' => 'Terminal final del servicio troncal.',
            ],
            // ── Terminales Capitán (sin horarios aún — se agregarán por planilla) ──
            ['slug' => 'parana-de-las-palmas', 'orden' => 20, 'nombre' => 'Paraná de las Palmas', 'rio' => 'Paraná de las Palmas', 'zona' => 'Segunda Sección', 'tipo_canal' => 'rio_principal'],
            ['slug' => 'paso-del-toro',        'orden' => 21, 'nombre' => 'Paso del Toro',        'zona' => 'Segunda Sección'],
            ['slug' => 'arroyo-toro',          'orden' => 22, 'nombre' => 'Arroyo Toro',          'rio' => 'Arroyo Toro',       'zona' => 'Segunda Sección'],
            ['slug' => 'arroyo-antequera',     'orden' => 23, 'nombre' => 'Arroyo Antequera',     'rio' => 'Arroyo Antequera',  'zona' => 'Segunda Sección'],
            ['slug' => 'puy-carabi',           'orden' => 24, 'nombre' => 'Puy Carabí',           'zona' => 'Segunda Sección'],
            ['slug' => 'canal-5',              'orden' => 25, 'nombre' => 'Canal 5',              'zona' => 'Segunda Sección'],
            ['slug' => 'estudiantes',          'orden' => 26, 'nombre' => 'Estudiantes',          'zona' => 'Segunda Sección'],
            ['slug' => 'fredes',               'orden' => 27, 'nombre' => 'Fredes',               'zona' => 'Segunda Sección'],
            ['slug' => 'arroyo-felicaria',     'orden' => 28, 'nombre' => 'Arroyo Felicaria',     'rio' => 'Arroyo Felicaria',  'zona' => 'Segunda Sección'],
            // ── Ramal Abra Vieja → Espera / Cruz Colorada ──────────────────────
            ['slug' => 'abra-vieja',           'orden' => 40, 'nombre' => 'Abra Vieja',           'rio' => 'Arroyo Abra Vieja', 'zona' => 'Primera Sección'],
            ['slug' => 'arroyo-espera',        'orden' => 41, 'nombre' => 'Espera',               'rio' => 'Arroyo Espera',     'zona' => 'Primera Sección'],
            ['slug' => 'torito',               'orden' => 42, 'nombre' => 'Torito',               'zona' => 'Primera Sección'],
            ['slug' => 'canal-8',              'orden' => 43, 'nombre' => 'Canal 8',              'zona' => 'Primera Sección'],
            ['slug' => 'cruz-colorada',        'orden' => 44, 'nombre' => 'Cruz Colorada',        'zona' => 'Primera Sección'],
            // ── Ramal San Antonio → Borasso / Canal del Este ────────────────────
            ['slug' => 'arroyo-dorado',        'orden' => 50, 'nombre' => 'Arroyo Dorado',        'rio' => 'Arroyo Dorado',     'zona' => 'Primera Sección'],
            ['slug' => 'boraso',               'orden' => 51, 'nombre' => 'Boraso',               'zona' => 'Primera Sección'],
            ['slug' => 'arroyon',              'orden' => 52, 'nombre' => 'Arroyón',              'zona' => 'Primera Sección'],
            ['slug' => 'arroyo-sabalos',       'orden' => 53, 'nombre' => 'Arroyo Sábalos',       'rio' => 'Arroyo Sábalos',    'zona' => 'Primera Sección'],
            ['slug' => 'rio-urion',            'orden' => 54, 'nombre' => 'Río Urión',            'rio' => 'Río Urión',         'zona' => 'Primera Sección'],
            ['slug' => 'canal-honda',          'orden' => 55, 'nombre' => 'Canal Honda',          'zona' => 'Primera Sección'],
            ['slug' => 'canal-del-este',       'orden' => 56, 'nombre' => 'Canal del Este',       'zona' => 'Primera Sección'],
            ['slug' => 'el-fondeadero',        'orden' => 57, 'nombre' => 'El Fondeadero',        'zona' => 'Primera Sección'],
        ];

        $m = [];
        foreach ($muellesData as $data) {
            $slug     = $data['slug'];
            $defaults = [
                'rio'         => null,
                'zona'        => 'Primera Sección',
                'tipo_canal'  => 'canal_secundario',
                'descripcion' => null,
                'latitud'     => null,
                'longitud'    => null,
                'activo'      => true,
            ];
            $m[$slug] = Muelle::updateOrCreate(
                ['slug' => $slug],
                array_merge($defaults, $data)
            );
        }

        // ── Pivot muelle_servicio (troncal Sarmiento + Capitán) ──────────────
        $troncal = [
            'tigre', 'tamarindos', 'gambado', 'raquel-ii', 'curubica', 'museo',
            'saucelandia', 'tres-bocas', 'esmeralda', 'villa-graciela',
            'publico-san-antonio', 'felipe', 'santa-fe',
            'boca-rama-negra', 'rama-negra', 'parana',
        ];
        foreach ($troncal as $orden => $slug) {
            $m[$slug]->servicios()->syncWithoutDetaching([
                $svc->id => ['orden' => $orden + 1, 'sentido' => 'ambos'],
            ]);
        }

        // ── Patrones IDA — Interisleña (desde Tigre) ─────────────────────────
        //
        // Fuente: planilla oficial Interisleña (temporada invierno)
        // Cada entrada es la hora de salida desde Tigre.
        // La hora en cada muelle = salida Tigre + offset de la tabla.
        //
        // Offsets en minutos desde Tigre:
        //   tigre=0  tamarindos=10  gambado=12  raquel-ii=14  curubica=20
        //   museo=28  saucelandia=32  tres-bocas=35  esmeralda=37
        //   villa-graciela=38  publico-san-antonio=40  felipe=42
        //   santa-fe=50  boca-rama-negra=65  rama-negra=70  parana=90
        //
        $offs = [
            'tigre'               =>  0,
            'tamarindos'          => 10,
            'gambado'             => 12,
            'raquel-ii'           => 14,
            'curubica'            => 20,
            'museo'               => 28,
            'saucelandia'         => 32,
            'tres-bocas'          => 35,
            'esmeralda'           => 37,
            'villa-graciela'      => 38,
            'publico-san-antonio' => 40,
            'felipe'              => 42,
            'santa-fe'            => 50,
            'boca-rama-negra'     => 65,
            'rama-negra'          => 70,
            'parana'              => 90,
        ];

        // Salidas desde Tigre por tipo de día
        // L–V: 17:30 exclusivo; 14:15 en lugar de 14:00; 21:00 solo L-V
        // Sáb: sin 17:30; 14:00 en lugar de 14:15; sin 21:00
        // Dom: sin 05:50, 06:50, 17:30; 14:00 en lugar de 14:15; sin 20:00, 21:00
        $grupos = [
            'lv'      => ['05:50','06:50','08:00','08:30','09:00','10:00','11:30','12:45','14:15','15:00','16:15','17:30','18:10','19:00','20:00','21:00'],
            'sabado'  => ['05:50','06:50','08:00','08:30','09:00','10:00','11:30','12:45','14:00','15:00','16:15','18:10','19:00','20:00'],
            'domingo' => ['08:00','08:30','09:00','10:00','11:30','12:45','14:00','15:00','16:15','18:10','19:00'],
        ];

        $now = now();
        $rows = [];

        foreach ($grupos as $tipoDia => $salidasTigre) {
            foreach ($salidasTigre as $horaTigre) {
                foreach ($offs as $slug => $minutos) {
                    $rows[] = [
                        'servicio_id'     => $svc->id,
                        'muelle_id'       => $m[$slug]->id,
                        'tipo_dia'        => $tipoDia,
                        'dia_semana'      => null,
                        'hora_referencia' => $this->addMinutes($horaTigre, $minutos),
                        'sentido'         => 'ida',
                        'fuente'          => 'oficial',
                        'visibilidad'     => 'publico',
                        'ventana_min'     => 15,
                        'temporada'       => 'todo',
                        'activo'          => true,
                        'validado_at'     => $now,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }
        }

        // Insertar en lotes para evitar timeouts
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('patrones')->insert($chunk);
        }
    }

    private function addMinutes(string $hora, int $minutos): string
    {
        return date('H:i:s', strtotime($hora) + $minutos * 60);
    }
}
