<?php

namespace App\Livewire\Problemas;

use Livewire\Component;

class Problema2 extends Component
{
    public $areaA;
    public $sigmaNomA;
    public $relacionA;
    public $ktA;
    public $sigmaMaxA;

    public $areaB;
    public $sigmaNomB;
    public $rdB;
    public $DdB;
    public $ktB;
    public $sigmaMaxB;

    public $seleccion;
    public $showImageStep4 = false;
    public $showImageStep9 = false;
    public $itemId;

    public int $currentStep = 1;

    public array $messages = [];

    private array $expected = [
        'areaA' => 320,
        'sigmaNomA' => 62.5,
        'relacionA' => 0.2,
        'ktA' => 2.5,
        'sigmaMaxA' => 156.25,

        'areaB' => 320,
        'sigmaNomB' => 62.5,
        'rdB' => 0.125,
        'DdB' => 1.25,
        'ktB' => 1.75,
        'sigmaMaxB' => 109.375,
    ];

    private function approxEqual($userValue, $expected, $tolAbs, $tolPct): bool
    {
        if ($userValue === null || !is_numeric($userValue)) {
            return false;
        }

        $userValue = (float) $userValue;

        return abs($userValue - $expected) <= $tolAbs
            || abs($userValue - $expected) <= abs($expected) * $tolPct;
    }

    private function setMessage(int $step, bool $ok, string $text): void
    {
        $this->messages[$step] = [
            'ok' => $ok,
            'text' => $text,
        ];
    }

    public function checkStep1()
    {
        $ok = $this->approxEqual($this->areaA, $this->expected['areaA'], 2, 0.02);

        if ($ok) {
            $this->setMessage(1, true, 'Correcto. A = (40 − 8) · 10 = 320 mm².');
            $this->currentStep = max($this->currentStep, 2);
        } else {
            $this->setMessage(1, false, 'Revisa el área neta: A = (W − Dtaladro) · t.');
        }
    }

    public function checkStep2()
    {
        $ok = $this->approxEqual($this->sigmaNomA, $this->expected['sigmaNomA'], 1, 0.03);

        if ($ok) {
            $this->setMessage(2, true, 'Correcto. σnom = 20000 N / 320 mm² = 62.5 MPa.');
            $this->currentStep = max($this->currentStep, 3);
        } else {
            $this->setMessage(2, false, 'Revisa: σnom = P / Anom. Recuerda que 1 N/mm² = 1 MPa.');
        }
    }

    public function checkStep3()
    {
        $ok = $this->approxEqual($this->relacionA, $this->expected['relacionA'], 0.01, 0.05);

        if ($ok) {
            $this->setMessage(3, true, 'Correcto. Dtaladro / W = 8 / 40 = 0.2.');
            $this->currentStep = max($this->currentStep, 4);
        } else {
            $this->setMessage(3, false, 'Revisa la relación geométrica: Dtaladro / W.');
        }
    }

    public function checkStep4()
    {
        $ok = $this->approxEqual($this->ktA, $this->expected['ktA'], 0.08, 0.05);

        if ($ok) {
            $this->setMessage(4, true, 'Correcto. De la gráfica, Kt ≈ 2.5.');
            $this->currentStep = max($this->currentStep, 5);
            $this->showImageStep4 = false; // ocultar si ya acertó
            
        } else {
            $this->setMessage(4, false, 'Revisa la gráfica A-15-4 para D/W = 0.2.');
            $this->showImageStep4 = true; // mostrar imagen de la gráfica para ayudar
            
        }
    }

    public function checkStep5()
    {
        $ok = $this->approxEqual($this->sigmaMaxA, $this->expected['sigmaMaxA'], 2, 0.03);

        if ($ok) {
            $this->setMessage(5, true, 'Correcto. σmax,A = 2.5 · 62.5 = 156.25 MPa.');
            $this->currentStep = max($this->currentStep, 6);
        } else {
            $this->setMessage(5, false, 'Revisa: σmax,A = Kt · σnom.');
        }
    }

    public function checkStep6()
    {
        $ok = $this->approxEqual($this->areaB, $this->expected['areaB'], 2, 0.02);

        if ($ok) {
            $this->setMessage(6, true, 'Correcto. A = d · t = 32 · 10 = 320 mm².');
            $this->currentStep = max($this->currentStep, 7);
        } else {
            $this->setMessage(6, false, 'Revisa el área mínima de la opción B: A = d · t.');
        }
    }

    public function checkStep7()
    {
        $ok = $this->approxEqual($this->sigmaNomB, $this->expected['sigmaNomB'], 1, 0.03);

        if ($ok) {
            $this->setMessage(7, true, 'Correcto. El esfuerzo nominal es el mismo: 62.5 MPa.');
            $this->currentStep = max($this->currentStep, 8);
        } else {
            $this->setMessage(7, false, 'Revisa: σnom = P / Anom.');
        }
    }

    public function checkStep8()
    {
        $ok1 = $this->approxEqual($this->rdB, $this->expected['rdB'], 0.01, 0.05);
        $ok2 = $this->approxEqual($this->DdB, $this->expected['DdB'], 0.03, 0.05);

        if ($ok1 && $ok2) {
            $this->setMessage(8, true, 'Correcto. r/d = 0.125 y D/d = 1.25.');
            $this->currentStep = max($this->currentStep, 9);
        } else {
            $this->setMessage(8, false, 'Revisa: r/d = 4/32 y D/d = 40/32.');
        }
    }

    public function checkStep9()
    {
        $ok = $this->approxEqual($this->ktB, $this->expected['ktB'], 0.08, 0.05);

        if ($ok) {
            $this->setMessage(9, true, 'Correcto. De la gráfica, Kt ≈ 1.75.');
            $this->currentStep = max($this->currentStep, 10);
            $this->showImageStep9 = false; // ocultar si ya acertó
        } else {
            $this->setMessage(9, false, 'Revisa la gráfica A-15-5 usando r/d = 0.125 y D/d = 1.25.');
            $this->showImageStep9 = true; // mostrar imagen de la gráfica para ayudar
        }
    }

    public function checkStep10()
    {
        $ok = $this->approxEqual($this->sigmaMaxB, $this->expected['sigmaMaxB'], 2, 0.03);

        if ($ok) {
            $this->setMessage(10, true, 'Correcto. σmax,B = 1.75 · 62.5 = 109.375 MPa.');
            $this->currentStep = max($this->currentStep, 11);
        } else {
            $this->setMessage(10, false, 'Revisa: σmax,B = Kt · σnom.');
        }
    }

    public function checkStep11()
    {
        if ($this->seleccion === 'B') {
            $this->setMessage(11, true, 'Correcto. La opción B es preferible porque genera menor esfuerzo máximo.');
            $this->currentStep = 12;
            $this->dispatch('problema-completado', itemId: $this->itemId);
        } else {
            $this->setMessage(11, false, 'Revisa la comparación: 109.375 MPa es menor que 156.25 MPa.');
        }
    }

    public function render()
    {
        return view('livewire.problemas.problema2');
    }
}
