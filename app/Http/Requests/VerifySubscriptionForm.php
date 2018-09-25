<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Projects\PianoLit\User;

class VerifySubscriptionForm extends FormRequest
{
    public $user;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->user = User::find($this->request->get('user_id'));

        return $this->user && ! $this->user->subscription()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'receipt_data' => 'required',
            'password' => 'required'
        ];
    }
}
