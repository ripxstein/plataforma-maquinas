<x-app-layout>
    <section class="content-section" id="concentracion">
        <div class="section-header">
          <h3>1. Concentración de esfuerzos (Kt)</h3>
        </div>
        <div class="section-body">
          <div class="tag">Definición</div>
          <p>
            La concentración de esfuerzos es el aumento localizado del esfuerzo provocado por discontinuidades geométricas. Se representa mediante la relación entre el esfuerzo máximo y el esfuerzo nominal.
          </p>
          <div class="formula">σ<sub>max</sub> = K<sub>t</sub> · σ<sub>nom</sub></div>

          <div class="footer-note" style="margin:14px 0 18px;">
            <strong>Modo guiado:</strong> en los problemas, la plataforma te pedirá respuestas paso a paso. No podrás avanzar hasta corregir.
          </div>

          <div class="accordion">
            <div class="accordion-item open" id="problema1">
              <button class="accordion-btn">Problema 1 (GUIADO): Placa con muescas <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  Una placa rectangular de acero tiene espesor <strong>t = 0.5 in</strong>, ancho <strong>w = 1.5 in</strong> y muescas semicirculares con radio <strong>r = 0.25 in</strong>. Está sometida a una carga axial estática de <strong>F = 6000 lbf</strong>. Se pide determinar el esfuerzo máximo en la zona de la muesca.
                </p>

                <div class="card" style="margin-top:12px;">
                  <h4>Resuelve paso a paso</h4>
                  <p style="margin-top:0;color:var(--gris);">Ingresa tus resultados. Usa unidades consistentes. La plataforma validará tus respuestas.</p>

                  <livewire:problemas.problema1 />
                </div>
              </div>
            </div>

            <div class="accordion-item open" id="problema2">
              <button class="accordion-btn">Problema 2: Selección de diseño (taladro vs filete) <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  Se compara una barra plana de <strong>W = 40 mm</strong>, <strong>t = 10 mm</strong>, reducida a <strong>d = 32 mm</strong> y sometida a <strong>P = 20 kN</strong> en dos opciones: un taladro central de 8 mm y un filete con radio de 4 mm.
                </p>
                <div class="grid-2">
                  <div class="card">
                    <h4>Opción A: Taladro central</h4>
                    <ul>
                      <li>Área nominal: <strong>(40 − 8) × 10 = 320 mm²</strong></li>
                      <li>Esfuerzo nominal: <strong>62.5 MPa</strong></li>
                      <li>Relación geométrica: <strong>D/W = 0.2</strong></li>
                      <li>Factor teórico: <strong>Kt ≈ 2.5</strong></li>
                      <li>Esfuerzo máximo: <strong>156.25 MPa</strong></li>
                    </ul>
                  </div>
                  <div class="card">
                    <h4>Opción B: Filete</h4>
                    <ul>
                      <li>Área nominal: <strong>32 × 10 = 320 mm²</strong></li>
                      <li>Esfuerzo nominal: <strong>62.5 MPa</strong></li>
                      <li>Relaciones: <strong>r/d = 0.125</strong>, <strong>D/d = 1.25</strong></li>
                      <li>Factor teórico: <strong>Kt ≈ 1.75</strong></li>
                      <li>Esfuerzo máximo: <strong>109.375 MPa</strong></li>
                    </ul>
                  </div>
                </div>
                <div class="footer-note" style="margin-top:16px;">
                  <strong>Conclusión:</strong> la opción con filete es preferible, ya que produce un esfuerzo máximo considerablemente menor y reduce la concentración de esfuerzos frente al taladro central.
                </div>
              </div>
            </div>
          </div>
        
        </div>
      </section>
</x-app-layout>