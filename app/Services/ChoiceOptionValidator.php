<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class ChoiceOptionValidator
{
    public function validate(array $options, bool $allowsMultipleCorrect, string $prefix = 'stepOptions'): void
    {
        $data = [];
        Arr::set($data, $prefix, $options);
        $validator = Validator::make($data, [
            $prefix => 'required|array|min:2',
            $prefix.'.*' => 'required|array',
            $prefix.'.*.id' => 'nullable|integer|distinct',
            $prefix.'.*.option_text' => 'nullable|string|required_without:'.$prefix.'.*.image_url',
            $prefix.'.*.image_url' => ['nullable', 'string', 'max:2048', $this->imageUrlRule()],
            $prefix.'.*.image_alt' => 'nullable|string|max:255',
            $prefix.'.*.is_correct' => 'sometimes|boolean',
        ], [
            $prefix.'.min' => 'Agrega al menos dos opciones.',
            $prefix.'.*.option_text.required_without' => 'Cada opción debe tener texto, una imagen o ambos.',
        ]);
        $validator->after(function ($validator) use ($options, $allowsMultipleCorrect, $prefix) {
            $correctCount = collect($options)->filter(fn ($option) => is_array($option) && ! empty($option['is_correct']))->count();
            if ($correctCount === 0) {
                $validator->errors()->add($prefix, 'Marca al menos una opción como correcta.');
            } elseif (! $allowsMultipleCorrect && $correctCount !== 1) {
                $validator->errors()->add($prefix, 'En respuesta única debes marcar solamente una opción correcta.');
            }
        });
        $validator->validate();
    }

    public function imageUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! is_string($value)) {
                $fail('La dirección de la imagen debe ser texto.');

                return;
            }
            // Admitir archivos locales y URLs web; nunca código ni imágenes base64 en la BD.
            $local = preg_match('~^/(?!/)[A-Za-z0-9_./%\-]+$~D', $value);
            $web = filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
            if (! $local && ! $web) {
                $fail('Usa una URL http/https válida o sube una imagen desde tu computadora.');
            }
        };
    }
}
