<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonDescFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->is_admin) {
            return false;
        }
        
        // スーパー管理者は全権限を持つ
        if ($user->admin_level === 'super') {
            return true;
        }
        
        // AdminPermissionServiceを使用して権限チェック
        $permissionService = app(\App\Services\Admin\AdminPermissionService::class);
        return $permissionService->hasPermission($user, 'locations.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $dungeonId = $this->route('id');
        
        return [
            'dungeon_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/', // 英数字、アンダースコア、ハイフンのみ
                Rule::unique('dungeons_desc', 'dungeon_id')->ignore($dungeonId),
            ],
            'dungeon_name' => [
                'required',
                'string',
                'max:255',
                'min:1',
            ],
            'dungeon_desc' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'dungeon_id' => 'ダンジョンID',
            'dungeon_name' => 'ダンジョン名',
            'dungeon_desc' => 'ダンジョン説明',
            'is_active' => 'アクティブ状態',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'dungeon_id.required' => 'ダンジョンIDは必須です。',
            'dungeon_id.string' => 'ダンジョンIDは文字列である必要があります。',
            'dungeon_id.max' => 'ダンジョンIDは:max文字以内で入力してください。',
            'dungeon_id.regex' => 'ダンジョンIDは英数字、アンダースコア、ハイフンのみ使用可能です。',
            'dungeon_id.unique' => 'このダンジョンIDは既に使用されています。',
            
            'dungeon_name.required' => 'ダンジョン名は必須です。',
            'dungeon_name.string' => 'ダンジョン名は文字列である必要があります。',
            'dungeon_name.max' => 'ダンジョン名は:max文字以内で入力してください。',
            'dungeon_name.min' => 'ダンジョン名は:min文字以上で入力してください。',
            
            'dungeon_desc.string' => 'ダンジョン説明は文字列である必要があります。',
            'dungeon_desc.max' => 'ダンジョン説明は:max文字以内で入力してください。',
            
            'is_active.boolean' => 'アクティブ状態は有効/無効で指定してください。',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // is_activeが送信されていない場合はfalseに設定
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => false]);
        } else {
            // チェックボックスの値を適切なbooleanに変換
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }

        // 入力値の前後の空白を削除
        if ($this->has('dungeon_name')) {
            $this->merge(['dungeon_name' => trim($this->input('dungeon_name'))]);
        }

        if ($this->has('dungeon_desc')) {
            $this->merge(['dungeon_desc' => trim($this->input('dungeon_desc'))]);
        }

        if ($this->has('dungeon_id')) {
            $this->merge(['dungeon_id' => trim($this->input('dungeon_id'))]);
        }
    }

    /**
     * Get validation data with default values for creation
     */
    public function getValidatedDataForCreate(): array
    {
        $validated = $this->validated();
        
        // 作成時のデフォルト値を設定
        // 新規作成時はフォームにis_activeフィールドがないため、明示的にtrueを設定
        $validated['is_active'] = true;
        
        return $validated;
    }

    /**
     * Get validation data for update
     */
    public function getValidatedDataForUpdate(): array
    {
        $validated = $this->validated();
        
        // 更新時はdungeon_idは変更しない
        unset($validated['dungeon_id']);
        
        return $validated;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->wantsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors()
            ], 422);
            
            throw new \Illuminate\Validation\ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}