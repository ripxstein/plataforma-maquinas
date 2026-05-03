<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Problem;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Módulo 1: Concentración de esfuerzos
        |--------------------------------------------------------------------------
        */

        $concentracion = Module::updateOrCreate(
            ['slug' => 'concentracion'],
            [
                'title' => 'Concentración de esfuerzos',
                'order' => 1,
            ]
        );

        $lecturaConcentracion = ModuleItem::updateOrCreate(
            [
                'module_id' => $concentracion->id,
                'order' => 1,
            ],
            [
                'title' => 'Lectura: Concentración de esfuerzos',
                'type' => 'lectura',
                'component' => null,
                'percentage' => 30,
                'content' => '
                    <div class="tag">Definición</div>

                    <p>
                        De acuerdo con Budynas y Nisbett (2021), la concentración de esfuerzos
                        es el aumento localizado del esfuerzo provocado por discontinuidades geométricas.
                        Se representa mediante la relación entre el esfuerzo máximo y el esfuerzo nominal.
                    </p>

                    <div class="formula">
                        σ<sub>max</sub> = K<sub>t</sub> · σ<sub>nom</sub>
                    </div>

                    <p>
                        Las siguientes gráficas permiten obtener el factor teórico de concentración
                        de esfuerzos <strong>K<sub>t</sub></strong> para barras rectangulares con
                        muescas o filetes.
                    </p>

                    <div class="grid-2 graficas-grid">
                        <div class="card grafica-card">
                            <div class="figura-descripcion">
                                <h4>Figura A-15-4</h4>
                                <p>
                                   Barra rectangular con muescas en flexión, σ = Mc/I, donde c = d/2, I = t·d³/12 y t es el espesor. Se utiliza para obtener
                    <strong>K<sub>t</sub></strong> a partir de las relaciones geométricas
                    <strong>r/d</strong> y <strong>w/d</strong>.
                                </p>
                            </div>

                            <img
                                src="/images/graficas/figura-a-15-4.png"
                                alt="Figura A-15-4"
                                class="grafica-img"
                            >
                        </div>

                        <div class="card grafica-card">
                            <div class="figura-descripcion">
                                <h4>Figura A-15-5</h4>
                                <p>
                                    Barra rectangular con filetes en tensión o compresión simple, σ = F/A, donde A = d·t y t es el espesor.
                    Se utiliza para determinar <strong>K<sub>t</sub></strong> usando
                    <strong>r/d</strong> y <strong>D/d</strong>.
                                </p>
                            </div>

                            <img
                                src="/images/graficas/figura-a-15-5.png"
                                alt="Figura A-15-5"
                                class="grafica-img"
                            >
                        </div>
                    </div>

                    <div class="footer-note" style="margin-top:18px;">
                        <strong>Fuente:</strong> Budynas &amp; Nisbett (2021).
                    </div>
                    <div class="footer-note" style="margin:14px 0 18px;">
            <strong>Modo guiado:</strong> en los problemas, la plataforma te pedirá respuestas paso a paso. No podrás avanzar hasta corregir.
          </div>
                ',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Módulo 2: Fallas por carga estática
        |--------------------------------------------------------------------------
        */

        $fallas = Module::updateOrCreate(
            ['slug' => 'fallas'],
            [
                'title' => 'Fallas por carga estática',
                'order' => 1,
            ]
        );

        ModuleItem::updateOrCreate(
            [
                'module_id' => $fallas->id,
                'order' => 1,
            ],
            [
                'title' => 'Lectura: Materiales dúctiles',
                'type' => 'lectura',
                'component' => null,
                'percentage' => 50,
                'content' => '
                    <div class="tag">Materiales dúctiles</div>

                    <p>
                        Los materiales dúctiles presentan deformación plástica apreciable antes de fallar.
                        En diseño mecánico se analizan comúnmente mediante las teorías de Tresca
                        y energía de distorsión o Von Mises.
                    </p>

                    <div class="formula">
                        τ<sub>max</sub> = (σ<sub>1</sub> − σ<sub>3</sub>) / 2
                    </div>

                    <div class="formula">
                        n = S<sub>y</sub> / (σ<sub>1</sub> − σ<sub>3</sub>)
                    </div>
                ',
            ]
        );

        ModuleItem::updateOrCreate(
            [
                'module_id' => $fallas->id,
                'order' => 2,
            ],
            [
                'title' => 'Lectura: Materiales frágiles',
                'type' => 'lectura',
                'component' => null,
                'percentage' => 50,
                'content' => '
                    <div class="tag">Materiales frágiles</div>

                    <p>
                        Los materiales frágiles fallan con poca deformación plástica. Para estos materiales
                        se utilizan criterios como Mohr Modificado o Coulomb-Mohr, considerando la resistencia
                        última a tensión y a compresión.
                    </p>

                    <div class="formula">
                        n = S<sub>ut</sub> / σ<sub>A</sub>
                    </div>
                ',
            ]
        );

        Problem::updateOrCreate(
            ['slug' => 'problema-1-placa-muescas'],
            [
                'module_item_id' => $lecturaConcentracion->id,
                'title' => 'Problema 1: Placa con muescas',
                'component' => 'problemas.problema1',
                'order' => 2,
                'content' => '<p>
                  Una placa rectangular de acero tiene espesor <strong>t = 0.5 in</strong>, ancho <strong>w = 1.5 in</strong> y muescas semicirculares con radio <strong>r = 0.25 in</strong>. Está sometida a una carga axial estática de <strong>F = 6000 lbf</strong>. Se pide determinar el esfuerzo máximo en la zona de la muesca.
                </p><div class="card ilustracion-card">

    <div class="ilustracion-header">
        <h4>Ilustración del problema</h4>
        <p>
            Barra rectangular con muesca sometida a carga axial.
            Se muestran las dimensiones geométricas y las fuerzas aplicadas.
        </p>
    </div><img
        src="/images/ilustraciones/A-1.png"
        alt="Ilustración de barra con muesca y carga axial"
        class="ilustracion-img"
    ><div class="ilustracion-footer">
        Fuente: Ilustración generada mediante IA
    </div>

</div>
',
                'percentage' => 35,
            ]
        );

        Problem::updateOrCreate(
            ['slug' => 'problema-2-taladro-filete'],
            [
                'module_item_id' => $lecturaConcentracion->id,
                'title' => 'Problema 2: Taladro vs Filete',
                'component' => 'problemas.problema2',
                'order' => 3,
                'content' => '<p>
                  Se compara una barra plana de <strong>W = 40 mm</strong>, <strong>t = 10 mm</strong>, reducida a <strong>d = 32 mm</strong> y sometida a <strong>P = 20 kN</strong> en dos opciones: un taladro central de 8 mm y un filete con radio de 4 mm.
                </p>

                <div class="card ilustracion-card">

    <div class="ilustracion-header">
        <h4>Ilustración del problema</h4>
        <p>
            Barra plana con reducción de sección por taladro o filete, sometida a carga axial.
            Se muestran las dimensiones geométricas y las fuerzas aplicadas.
        </p>
    </div>

    <img
        src="/images/ilustraciones/B-1.png"
        alt="Ilustración de barra con muesca y carga axial"
        class="ilustracion-img"
    >

    <div class="ilustracion-footer">
        Fuente: Ilustración generada mediante IA
    </div>

</div>',
                'percentage' => 35,
            ]
        );

    }
}
