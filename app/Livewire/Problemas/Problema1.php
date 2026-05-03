<?php

namespace App\Livewire\Problemas;

use Livewire\Component;

class Problema1 extends Component
{
    public $d;
    public $A;
    public $snom;
    public $kt;
    public $smax;
    public $showImageStep4 = false;
    public $itemId;


    public int $currentStep = 1;

    public array $messages = [];

    private array $expected = [
        'd' => 1.0,
        'A' => 0.5,
        'snom' => 12000,
        'kt' => 1.9,
        'smax' => 22800,
    ];

    public function approxEqual($userValue, $expected, $tolAbs, $tolPct): bool
    {
        if ($userValue === null || !is_numeric($userValue)) {
            return false;
        }

        $userValue = (float) $userValue;

        $absOk = abs($userValue - $expected) <= $tolAbs;
        $pctOk = abs($userValue - $expected) <= abs($expected) * $tolPct;

        return $absOk || $pctOk;
    }

    public function checkStep1()
    {
        if ($this->approxEqual($this->d, $this->expected['d'], 0.01, 0.02)) {
            $this->messages[1] = ['ok' => true, 'text' => 'Correcto'];
            $this->currentStep = 2;
        } else {
            $this->messages[1] = ['ok' => false, 'text' => 'Revisa d = w − 2r'];
        }
    }

    public function checkStep2()
    {
        if ($this->approxEqual($this->A, $this->expected['A'], 0.01, 0.02)) {
            $this->messages[2] = ['ok' => true, 'text' => 'Correcto'];
            $this->currentStep = 3;
        } else {
            $this->messages[2] = ['ok' => false, 'text' => 'Revisa A = d · t'];
        }
    }

    public function checkStep3()
    {
        if ($this->approxEqual($this->snom, $this->expected['snom'], 100, 0.02)) {
            $this->messages[3] = ['ok' => true, 'text' => 'Correcto'];
            $this->currentStep = 4;
        } else {
            $this->messages[3] = ['ok' => false, 'text' => 'Revisa σnom = F / A'];
        }
    }

    public function checkStep4()
{
    if ($this->approxEqual($this->kt, $this->expected['kt'], 0.05, 0.03)) {
        $this->messages[4] = ['ok' => true, 'text' => 'Correcto'];
        $this->currentStep = 5;

        $this->showImageStep4 = false; // ocultar si ya acertó
    } else {
        $this->messages[4] = [
            'ok' => false,
            'text' => 'Revisa cómo obtener Kt de la gráfica'
        ];

        $this->showImageStep4 = true; // mostrar imagen de ayuda
    }
}

    public function checkStep5()
    {
        if ($this->approxEqual($this->smax, $this->expected['smax'], 200, 0.02)) {
            $this->messages[5] = ['ok' => true, 'text' => 'Correcto. ¡Completado!'];
            $this->currentStep = 6;
             $this->dispatch('problema-completado', itemId: $this->itemId);
        } else {
            $this->messages[5] = ['ok' => false, 'text' => 'Revisa σmax = Kt · σnom'];
            
        }
    }

    public function render()
    {
        return view('livewire.problemas.problema1');
    }
}