<x-app-layout>
    <section class="content-section" id="fallas">
        <div class="section-header">
          <h3>2. Fallas resultantes de carga estática</h3>
        </div>
        <div class="section-body">
          <div class="grid-2">
            <div class="card" id="ductiles">
              <h4>2.1 Materiales dúctiles</h4>
              <p><strong>Teoría del esfuerzo cortante máximo (ECM o Tresca)</strong></p>
              <p>
                La fluencia inicia cuando el esfuerzo cortante máximo en el material iguala al esfuerzo cortante máximo de una prueba de tensión simple al momento de fluencia.
              </p>
              <div class="formula">τ<sub>max</sub> = (σ<sub>1</sub> − σ<sub>3</sub>) / 2</div>
              <div class="formula">n = S<sub>y</sub> / (σ<sub>1</sub> − σ<sub>3</sub>)</div>

              <p><strong>Teoría de la energía de distorsión (ED o Von Mises)</strong></p>
              <p>
                La falla ocurre cuando la energía de distorsión por unidad de volumen alcanza la energía de distorsión correspondiente al punto de fluencia en tensión simple.
              </p>
              <div class="formula">σ′ = √(σ<sub>x</sub><sup>2</sup> − σ<sub>x</sub>σ<sub>y</sub> + σ<sub>y</sub><sup>2</sup> + 3τ<sub>xy</sub><sup>2</sup>)</div>
              <div class="formula">n = S<sub>y</sub> / σ′</div>
            </div>

            <div class="card" id="fragiles">
              <h4>2.2 Materiales frágiles</h4>
              <p><strong>Teoría de Mohr Modificado</strong></p>
              <p>
                Es adecuada para materiales con distinta resistencia a la tracción y a la compresión, utilizando <strong>Sut</strong> y <strong>Suc</strong>. Se analiza el cuadrante donde se ubican los esfuerzos principales planos <strong>σA</strong> y <strong>σB</strong>.
              </p>
              <ul>
                <li><strong>Caso 1:</strong> σA ≥ σB ≥ 0, falla por tracción.</li>
                <li><strong>Caso 2:</strong> σA ≥ 0 ≥ σB, combinación de tracción y compresión.</li>
                <li><strong>Caso 3:</strong> 0 ≥ σA ≥ σB, falla por compresión.</li>
              </ul>
              <div class="formula">Si |σ<sub>B</sub>/σ<sub>A</sub>| ≤ 1 → n = S<sub>ut</sub> / σ<sub>A</sub></div>
              <div class="formula">Si |σ<sub>B</sub>/σ<sub>A</sub>| &gt; 1 → ((S<sub>uc</sub> − S<sub>ut</sub>)σ<sub>A</sub>)/(S<sub>uc</sub>S<sub>ut</sub>) − σ<sub>B</sub>/S<sub>uc</sub> = 1/n</div>
              <div class="formula">En compresión pura → n = −S<sub>uc</sub> / σ<sub>B</sub></div>
            </div>
          </div>

          <div class="objective-box">
            <h4 style="margin-top:0; color:var(--azul-secundario);">Matriz de selección de teoría de falla</h4>
            <p>
              Primero se identifica el tipo de material según su elongación. Si <strong>ϵ ≥ 0.05</strong> se considera dúctil y se trabaja con <strong>Sy</strong>, aplicando Von Mises o Tresca. Si <strong>ϵ &lt; 0.05</strong> se considera frágil y se trabaja con <strong>Sut</strong> y <strong>Suc</strong>, aplicando Mohr Modificado o Coulomb-Mohr.
            </p>
          </div>
        </div>
      </section>
</x-app-layout>