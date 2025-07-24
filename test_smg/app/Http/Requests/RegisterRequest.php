<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z0-9\p{Han}\p{Hiragana}\p{Katakana}ー\s]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns',
                'max:255',
                'unique:'.User::class,
            ],
            'password' => array_filter([
                'required',
                'confirmed',
                Rules\Password::min(8)
                    ->letters()
                    ->numbers()
                    ->when(!app()->environment('testing'), fn($rule) => $rule->uncompromised()),
            ]),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '冒険者名は必須です。',
            'name.min' => '冒険者名は2文字以上で入力してください。',
            'name.max' => '冒険者名は255文字以下で入力してください。',
            'name.regex' => '冒険者名に使用できない文字が含まれています。日本語、英数字、スペースのみ使用可能です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'password.required' => 'パスワードは必須です。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワード確認が一致しません。',
            'password.letters' => 'パスワードには英字を含めてください。',
            'password.numbers' => 'パスワードには数字を含めてください。',
            'password.uncompromised' => 'このパスワードは安全ではありません。別のパスワードを選択してください。',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '冒険者名',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
            'password_confirmation' => 'パスワード確認',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // 成功時の追加処理があれば記載
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $response = redirect()->route('register')
            ->withInput($this->except(['password', 'password_confirmation']))
            ->withErrors($validator);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
