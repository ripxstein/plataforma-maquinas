<?php

namespace App\Services;

class AnswerEvaluator
{
    public function numeric(mixed $answer, mixed $expected, float $absolute, float $relative): bool
    {
        if (! is_numeric($answer) || ! is_numeric($expected)) {
            return false;
        }
        $difference = abs((float) $answer - (float) $expected);

        return is_finite($difference) && ($difference <= $absolute || $difference <= abs((float) $expected) * $relative);
    }

    /** Las opciones usan ID en alumnos e índice en el simulador; el adaptador proporciona la clave. */
    public function evaluate(array $step, mixed $answer, array $options = []): bool
    {
        if (! is_scalar($answer) && $answer !== null) {
            return false;
        }
        switch ($step['answer_type']) {
            case 'numeric':
                $tolerance = (float) ($step['tolerance'] ?? 0.01);
                if (($step['tolerance_type'] ?? 'absolute') === 'percentage') {
                    return $this->numeric($answer, $step['correct_answer'], 0.0001, $tolerance > 1 ? $tolerance / 100 : $tolerance);
                }

                return $this->numeric($answer, $step['correct_answer'], $tolerance, 0.02);
            case 'multiple_choice':
                foreach ($options as $key => $option) {
                    if ((string) $key === (string) $answer && ! empty($option['is_correct'])) {
                        return true;
                    }
                }

                return false;
            case 'true_false':
                $actual = $this->boolean($answer);

                return $actual !== null && $actual === $this->boolean($step['correct_answer']);
            case 'text':
                $actual = mb_strtolower(trim((string) $answer));

                return $actual !== '' && $actual === mb_strtolower(trim((string) $step['correct_answer']));
            default:
                return false;
        }
    }

    private function boolean(mixed $value): ?bool
    {
        return match (mb_strtolower(trim((string) $value))) {
            '1', 'true', 'verdadero', 'v', 't' => true,
            '0', 'false', 'falso', 'f' => false,
            default => null,
        };
    }
}
