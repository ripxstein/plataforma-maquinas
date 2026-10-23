<?php

namespace App\Services;

use App\Models\Problem;
use App\Models\ProblemStep;
use App\Models\ProblemStepOption;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProblemWriter
{
    public function saveDetails(?int $id, array $attributes): Problem
    {
        Validator::make($attributes, [
            'module_item_id' => 'required|exists:module_items,id',
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('problems', 'slug')->ignore($id)],
            'order' => 'required|integer|min:1',
            'percentage' => 'required|integer|min:0|max:100',
            'component' => 'required|string|max:255',
        ])->validate();

        $problem = $id ? Problem::findOrFail($id) : new Problem;
        $problem->fill(Arr::only($attributes, $problem->getFillable()))->save();

        return $problem;
    }

    /** Guarda todo el agregado o revierte; los IDs de pasos/opciones deben pertenecer a su padre. */
    public function save(?int $id, array $attributes, array $steps): array
    {
        Validator::make(['stepsData' => $steps], [
            'stepsData' => 'required|array|min:1',
            'stepsData.*.id' => 'nullable|integer|distinct',
            'stepsData.*.title' => 'required|string|max:255',
            'stepsData.*.answer_type' => 'required|in:numeric,multiple_choice,true_false,text,informational',
            'stepsData.*.tolerance' => 'nullable|numeric|min:0',
            'stepsData.*.tolerance_type' => 'nullable|in:absolute,percentage',
            'stepsData.*.options' => 'sometimes|array',
            'stepsData.*.options.*.id' => 'nullable|integer',
            'stepsData.*.options.*.option_text' => 'nullable|string',
            'stepsData.*.options.*.is_correct' => 'sometimes|boolean',
        ])->validate();

        return DB::transaction(function () use ($id, $attributes, $steps) {
            $problem = $this->saveDetails($id, $attributes);
            $kept = [];
            foreach ($steps as $index => &$data) {
                $step = ! empty($data['id']) ? $problem->steps()->findOrFail($data['id']) : new ProblemStep;
                $step->fill(Arr::only($data, $step->getFillable()));
                $step->problem_id = $problem->id;
                $step->step_number = $index + 1;
                $step->save();
                $kept[] = $data['id'] = $step->id;
                $optionIds = [];
                if ($step->answer_type === 'multiple_choice') {
                    foreach ($data['options'] ?? [] as $key => $optionData) {
                        if (trim($optionData['option_text'] ?? '') === '') {
                            continue;
                        }
                        $option = ! empty($optionData['id']) ? $step->options()->findOrFail($optionData['id']) : new ProblemStepOption;
                        $option->fill([
                            'problem_step_id' => $step->id,
                            'option_text' => $optionData['option_text'],
                            'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                            'order' => $key + 1,
                        ])->save();
                        $optionIds[] = $data['options'][$key]['id'] = $option->id;
                    }
                }
                // Eliminación por modelo para conservar los eventos de invalidación.
                $step->options()->whereNotIn('id', $optionIds)->get()->each->delete();
            }
            unset($data);
            $problem->steps()->whereNotIn('id', $kept)->get()->each->delete();

            return ['problem' => $problem, 'steps' => $steps];
        });
    }

    public function duplicate(int $id): Problem
    {
        return DB::transaction(function () use ($id) {
            $original = Problem::with('steps.options')->findOrFail($id);
            $copy = new Problem($original->only($original->getFillable()));
            $copy->title = 'Copia de '.$original->title;
            $copy->slug = Str::limit(Str::slug($copy->title), 210, '').'-'.Str::uuid();
            $copy->order = (Problem::where('module_item_id', $original->module_item_id)->max('order') ?? 0) + 1;
            $copy->component = $original->component ?: 'problemas.problema-dinamico';
            $copy->save();
            foreach ($original->steps as $step) {
                $newStep = new ProblemStep($step->only($step->getFillable()));
                $newStep->problem_id = $copy->id;
                $newStep->save();
                foreach ($step->options as $option) {
                    $newOption = new ProblemStepOption($option->only($option->getFillable()));
                    $newOption->problem_step_id = $newStep->id;
                    $newOption->save();
                }
            }

            return $copy;
        });
    }
}
