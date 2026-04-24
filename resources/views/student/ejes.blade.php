<x-app-layout>
    <section class="content-section" id="ejes">
        <div class="section-header">
          <h3>4. Diseño de ejes</h3>
        </div>
        <div class="section-body">
          <div class="accordion">
            <div class="accordion-item open" id="problema6">
              <button class="accordion-btn">Problema 6: Contra-eje con poleas <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  El eje soporta dos poleas. Se tiene <strong>Tout = 33 Nm</strong>, <strong>M<sub>Ay</sub> = 49.02 Nm</strong>, <strong>M<sub>Az</sub> = 32.08 Nm</strong>, material con <strong>Sy = 370 MPa</strong> y factor de diseño <strong>n = 3.0</strong>.
                </p>
                <div class="formula">M<sub>max</sub> = √(49.02² + 32.08²) = 58.58 Nm</div>
                <div class="formula">d = [ (32n / πS<sub>y</sub>) · √(M<sub>max</sub><sup>2</sup> + 3T²/4) ]<sup>1/3</sup></div>
                <div class="formula">d ≈ 17.5 mm</div>
                <p>Se selecciona diámetro comercial de <strong>18 mm</strong> o <strong>20 mm</strong> para mayor seguridad.</p>
              </div>
            </div>

            <div class="accordion-item open" id="problema7">
              <button class="accordion-btn">Problema 7: Eje con cojinetes y material frágil <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  Eje giratorio apoyado en cojinetes, con momentos <strong>M<sub>B</sub> = 1971.7 lb-in</strong>, <strong>M<sub>C</sub> = 1853.3 lb-in</strong>, par <strong>T = 1500 lb-in</strong>, hierro fundido grado 25 con <strong>Sut = 25 kpsi</strong>, <strong>Suc = 97 kpsi</strong> y <strong>n = 2.5</strong>.
                </p>
                <p>
                  Al ser material frágil, se emplea la teoría de <strong>Mohr Modificado</strong>. La sección crítica está en <strong>B</strong> por presentar el momento flector mayor.
                </p>
                <div class="formula">σ<sub>A,B</sub> = (16 / πd³) · (M<sub>max</sub> ± √(M<sub>max</sub>² + T²))</div>
                <div class="formula">d ≈ 1.31 in</div>
                <p>Se concluye que el diámetro mínimo requerido es aproximadamente <strong>1.31 pulgadas</strong>.</p>
              </div>
            </div>

            <div class="accordion-item open" id="problema8">
              <button class="accordion-btn">Problema 8: Material frágil con esfuerzos principales dados <span>▾</span></button>
              <div class="accordion-content">
                <p>
                  Un componente de hierro fundido grado 20 con <strong>Sut = 22 kpsi</strong> y <strong>Suc = 85 kpsi</strong> está sometido a <strong>σ1 = 9 kpsi</strong> y <strong>σ2 = −5 kpsi</strong>.
                </p>
                <p>
                  Como <strong>σA &gt; 0</strong> y <strong>σB &lt; 0</strong>, se analiza el cuadrante 4. La relación <strong>|σB/σA| = 5/9 ≈ 0.556</strong> es menor que 1, por lo que domina el criterio de tracción.
                </p>
                <div class="formula">n = S<sub>ut</sub> / σ<sub>A</sub> = 22 / 9 = 2.44</div>
                <p>El factor de seguridad calculado es <strong>2.44</strong>.</p>
              </div>
            </div>
          </div>
        </div>
      </section>
</x-app-layout>