<x-app-layout>
    <section class="content-section" id="aplicacion">
        <div class="section-header">
          <h3>3. Aplicación de teorías de falla</h3>
        </div>
        <div class="section-body">
          <div class="accordion">
            <div class="accordion-item open" id="problema3">
              <button class="accordion-btn">Problema 3: Estado de esfuerzo plano <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  Un componente de acero con <strong>Sy = 350 MPa</strong> presenta <strong>σx = 100 MPa</strong>, <strong>σy = 100 MPa</strong> y <strong>τxy = 0</strong>. Se determina el factor de seguridad mediante Von Mises.
                </p>
                <div class="formula">σ′ = √(100² − 100·100 + 100² + 3·0²) = 100 MPa</div>
                <div class="formula">n = 350 / 100 = 3.5</div>
                <p>El componente no falla por fluencia en estas condiciones.</p>
              </div>
            </div>

            <div class="accordion-item open" id="problema4">
              <button class="accordion-btn">Problema 4: Barra en voladizo <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  Barra cilíndrica en voladizo de <strong>d = 15 mm</strong>, <strong>L = 120 mm</strong>, acero con <strong>Sy = 280 MPa</strong>, sometida a <strong>F = 0.55 kN</strong>, <strong>P = 4 kN</strong> y <strong>T = 25 Nm</strong>.
                </p>
                <ul>
                  <li>Esfuerzo axial: <strong>22.64 MPa</strong></li>
                  <li>Esfuerzo por flexión: <strong>199.20 MPa</strong></li>
                  <li>Esfuerzo normal total: <strong>221.84 MPa</strong></li>
                  <li>Esfuerzo cortante por torsión: <strong>37.73 MPa</strong></li>
                </ul>
                <div class="formula">σ′ ≈ 231.27 MPa</div>
                <div class="formula">n = 280 / 231.27 ≈ 1.21</div>
              </div>
            </div>

            <div class="accordion-item open" id="problema5">
              <button class="accordion-btn">Problema 5: Pedal de bicicleta <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  El brazo del pedal tiene <strong>L1 = 170 mm</strong>, el eje perpendicular <strong>L2 = 60 mm</strong> y diámetro <strong>d = 15 mm</strong>. El material tiene <strong>Sy = 350 MPa</strong> y la fuerza aplicada es <strong>F = 1500 N</strong>.
                </p>
                <ul>
                  <li>Momento flector: <strong>90 Nm</strong></li>
                  <li>Torque: <strong>255 Nm</strong></li>
                  <li>Esfuerzo por flexión: <strong>271.6 MPa</strong></li>
                  <li>Esfuerzo cortante por torsión: <strong>384.8 MPa</strong></li>
                </ul>
                <div class="formula">σ′ ≈ 719.7 MPa</div>
                <div class="formula">n = 350 / 719.7 ≈ 0.486</div>
                <p><strong>Conclusión:</strong> con n &lt; 1, el diseño falla por fluencia bajo la carga indicada.</p>
              </div>
            </div>
          </div>
        </div>
      </section>
</x-app-layout>