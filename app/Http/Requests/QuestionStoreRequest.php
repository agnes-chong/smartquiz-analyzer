<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class QuestionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'type'    => ['required',Rule::in(['mcq','tf','short'])],
            'scoring' => ['required',Rule::in(['exact','partial','negative'])],
            'marks'   => ['required','numeric','min:0'],
            'penalty' => ['nullable','numeric','min:0'],
            'prompt'  => ['required','string','max:2000'],
            'options' => [request('type')==='mcq'?'required':'nullable','array','min:2'],
            'answer'  => ['required'],
        ];
    }
}
