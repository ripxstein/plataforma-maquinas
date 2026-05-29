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

    public $problemId;
    public $showImageStep4 = false;

    public int $currentStep = 1;

    public bool $askNextStep = false;
    public $nextStepAnswer = null;
    public $nextStepFor = null;

    public array $messages = [];

    private array $expected = [
        'd' => 1.0,
        'A' => 0.5,
        'snom' => 12000,
        'kt' => 1.9,
        'smax' => 22800,
    ];

    private array $nextStepExpected = [
        1 => 'area',
        2 => 'esfuerzo_nominal',
        3 => 'kt',
        4 => 'esfuerzo_maximo',
    ];

    private array $nextStepReminders = [
        1 => 'Después de obtener d, debes calcular el área mínima: A = d · t.',
        2 => 'Después del área, debes calcular el esfuerzo nominal: σnom = F / A.',
        3 => 'Después del esfuerzo nominal, debes obtener Kt desde la gráfica.',
        4 => 'Después de Kt, debes calcular σmax = Kt · σnom.',
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

    private function askWhatIsNext(int $step): void
    {
        $this->askNextStep = true;
        $this->nextStepFor = $step;
        $this->nextStepAnswer = null;
    }

    public function checkNextStep()
    {
        if (!$this->nextStepFor) {
            return;
        }

        $expected = $this->nextStepExpected[$this->nextStepFor] ?? null;

        if ($this->nextStepAnswer === $expected) {
            $this->messages['next'] = [
                'ok' => true,
                'text' => 'Correcto. Puedes continuar con el siguiente paso.',
            ];

            $this->currentStep = $this->nextStepFor + 1;
            $this->askNextStep = false;
            $this->nextStepFor = null;
            $this->nextStepAnswer = null;
        } else {
            $this->messages['next'] = [
                'ok' => false,
                'text' => $this->nextStepReminders[$this->nextStepFor],
            ];
        }
    }

    public function checkStep1()
    {
        if ($this->approxEqual($this->d, $this->expected['d'], 0.01, 0.02)) {
            $this->messages[1] = [
                'ok' => true,
                'text' => 'Correcto. d = w − 2r = 1.0 in.',
            ];

            $this->askWhatIsNext(1);
        } else {
            $this->messages[1] = [
                'ok' => false,
                'text' => 'Revisa d = w − 2r. Sustituye w = 1.5 in y r = 0.25 in.',
            ];
        }
    }

    public function checkStep2()
    {
        if ($this->approxEqual($this->A, $this->expected['A'], 0.01, 0.02)) {
            $this->messages[2] = [
                'ok' => true,
                'text' => 'Correcto. A = d · t = 0.5 in².',
            ];

            $this->askWhatIsNext(2);
        } else {
            $this->messages[2] = [
                'ok' => false,
                'text' => 'Revisa A = d · t. Usa d = 1.0 in y t = 0.5 in.',
            ];
        }
    }

    public function checkStep3()
    {
        if ($this->approxEqual($this->snom, $this->expected['snom'], 100, 0.02)) {
            $this->messages[3] = [
                'ok' => true,
                'text' => 'Correcto. σnom = F / A = 12000 psi.',
            ];

            $this->askWhatIsNext(3);
        } else {
            $this->messages[3] = [
                'ok' => false,
                'text' => 'Revisa σnom = F / A. Usa F = 6000 lbf y A = 0.5 in².',
            ];
        }
    }

    public function checkStep4()
    {
        if ($this->approxEqual($this->kt, $this->expected['kt'], 0.05, 0.03)) {
            $this->messages[4] = [
                'ok' => true,
                'text' => 'Correcto. Kt ≈ 1.9.',
            ];

            $this->showImageStep4 = false;
            $this->askWhatIsNext(4);
        } else {
            $this->messages[4] = [
                'ok' => false,
                'text' => 'Revisa cómo obtener Kt de la gráfica usando r/d y w/d.',
            ];

            $this->showImageStep4 = true;
        }
    }

    public function checkStep5()
    {
        if ($this->approxEqual($this->smax, $this->expected['smax'], 200, 0.02)) {
            $this->messages[5] = [
                'ok' => true,
                'text' => 'Correcto. σmax = 22800 psi. ¡Problema completado!',
            ];

            $this->currentStep = 6;

            $this->dispatch('problema-completado', problemId: $this->problemId);
        } else {
            $this->messages[5] = [
                'ok' => false,
                'text' => 'Revisa σmax = Kt · σnom.',
            ];
        }
    }

    public function render()
    {
        return view('livewire.problemas.problema1');
    }
}