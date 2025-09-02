<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachFloorsRequest extends FormRequest
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
        return [
            'floor_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'floor_ids.*' => [
                'required',
                'string',
                'exists:routes,id',
                // フロアがダンジョンカテゴリーであることを確認
                Rule::exists('routes', 'id')->where('category', 'dungeon'),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'floor_ids' => 'フロアID',
            'floor_ids.*' => 'フロアID',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'floor_ids.required' => 'アタッチするフロアを選択してください。',
            'floor_ids.array' => 'フロアIDの形式が正しくありません。',
            'floor_ids.min' => '最低1つのフロアを選択してください。',
            'floor_ids.*.required' => 'フロアIDが不正です。',
            'floor_ids.*.string' => 'フロアIDは文字列である必要があります。',
            'floor_ids.*.exists' => '指定されたフロアが存在しないか、ダンジョンカテゴリーではありません。',
        ];
    }

    /**
     * 選択されたフロアのバリデーション
     * コントローラーで呼び出して追加チェックを実行
     */
    public function validateFloors(): array
    {
        $floorIds = $this->validated()['floor_ids'];
        
        // 選択されたフロアの詳細情報を取得
        $floors = \App\Models\Route::whereIn('id', $floorIds)
                                  ->where('category', 'dungeon')
                                  ->get();

        $validationErrors = [];
        $warnings = [];

        foreach ($floors as $floor) {
            // 既に親に紐づいている場合は警告
            if ($floor->dungeon_id) {
                $currentParent = \App\Models\DungeonDesc::where('dungeon_id', $floor->dungeon_id)->first();
                if ($currentParent) {
                    $warnings[] = [
                        'floor_id' => $floor->id,
                        'floor_name' => $floor->name,
                        'message' => "フロア「{$floor->name}」は既に「{$currentParent->dungeon_name}」に紐づいています。",
                        'current_parent' => $currentParent->dungeon_name
                    ];
                }
            }
        }

        return [
            'floors' => $floors,
            'validation_errors' => $validationErrors,
            'warnings' => $warnings
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->wantsJson()) {
            $response = response()->json([
                'success' => false,
                'error' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors()
            ], 422);
            
            throw new \Illuminate\Validation\ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}