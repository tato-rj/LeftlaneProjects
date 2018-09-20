<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Projects\PianoLit\User;

class SubscribeUser extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return User::find($this->request->get('user_id'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required'
        ];
    }
}
