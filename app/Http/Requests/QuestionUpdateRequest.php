<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class QuestionUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()?->tokenCan('teacher') || $this->user()?->tokenCan('admin');;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $type=$this->input('type');
        return [
            'type'    => ['sometimes',Rule::in(['mcq','tf','short'])],
            'scoring' => ['sometimes',Rule::in(['exact','partial','negative'])],
            'marks'   => ['sometimes','numeric','min:0'],
            'penalty' => ['nullable','numeric','min:0'],
            'prompt'  => ['sometimes','string','max:2000'],
            'options' => [$type==='mcq'?'sometimes':'nullable','array'],
            'answer'  => ['sometimes'],
        ];
    }
}
