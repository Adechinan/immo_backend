<?php
// RegisterRequest.php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'prenom'   => 'required|string|max:100',
            'nom'      => 'required|string|max:100',
            'tel'      => 'nullable|string|max:20',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
