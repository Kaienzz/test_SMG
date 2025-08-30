<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Route;
use App\Models\RouteConnection;
use App\Helpers\ActionLabel;

class UpdateRouteConnectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $connectionId = $this->route('route_connection');
        
        return [
            // Basic required fields
            'source_location_id' => [
                'required',
                'string',
                'exists:routes,id'
            ],
            'target_location_id' => [
                'required', 
                'string',
                'exists:routes,id',
                'different:source_location_id'
            ],
            
            // Position fields
            'source_position' => [
                'nullable',
                'integer',
                'between:0,100'
            ],
            'target_position' => [
                'nullable', 
                'integer',
                'between:0,100'
            ],
            
            // Optional enhancement fields
            'edge_type' => [
                'nullable',
                Rule::in(['normal', 'branch', 'portal', 'exit', 'enter'])
            ],
            'is_enabled' => 'nullable|boolean',
            'action_label' => [
                'nullable',
                Rule::in(array_keys(ActionLabel::getAllActionLabels()))
            ],
            'keyboard_shortcut' => [
                'nullable',
                Rule::in(['up', 'down', 'left', 'right'])
            ],
            
            // Legacy fields (will be removed later)
            'connection_type' => 'nullable|string',
            'position' => 'nullable|integer',
            'direction' => 'nullable|string'
        ];
    }
    
    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateCategoryPositionRules($validator);
            $this->validateKeyboardShortcutUniqueness($validator);
            $this->validateConnectionDuplication($validator);
        });
    }
    
    /**
     * Validate category-position rules
     */
    private function validateCategoryPositionRules($validator)
    {
        $sourceLocationId = $this->input('source_location_id');
        $targetLocationId = $this->input('target_location_id');
        $sourcePosition = $this->input('source_position');
        $targetPosition = $this->input('target_position');
        
        if ($sourceLocationId) {
            $sourceLocation = Route::find($sourceLocationId);
            if ($sourceLocation) {
                // Source category rules
                if ($sourceLocation->category === 'town' && $sourcePosition !== null) {
                    $validator->errors()->add('source_position', 'Towns cannot have source positions.');
                }
                if (in_array($sourceLocation->category, ['road', 'dungeon']) && $sourcePosition === null) {
                    $validator->errors()->add('source_position', 'Roads and dungeons must have source positions.');
                }
            }
        }
        
        if ($targetLocationId) {
            $targetLocation = Route::find($targetLocationId);
            if ($targetLocation) {
                // Target category rules
                if ($targetLocation->category === 'town' && $targetPosition !== null) {
                    $validator->errors()->add('target_position', 'Towns cannot have target positions.');
                }
                if (in_array($targetLocation->category, ['road', 'dungeon']) && $targetPosition === null) {
                    $validator->errors()->add('target_position', 'Roads and dungeons must have target positions.');
                }
            }
        }
    }
    
    /**
     * Validate keyboard shortcut uniqueness per source location (excluding current record)
     */
    private function validateKeyboardShortcutUniqueness($validator)
    {
        $connectionId = $this->route('route_connection');
        $sourceLocationId = $this->input('source_location_id');
        $keyboardShortcut = $this->input('keyboard_shortcut');
        
        if ($sourceLocationId && $keyboardShortcut) {
            $existing = RouteConnection::where('source_location_id', $sourceLocationId)
                                     ->where('keyboard_shortcut', $keyboardShortcut)
                                     ->where('id', '!=', $connectionId)
                                     ->first();
                                     
            if ($existing) {
                $validator->errors()->add('keyboard_shortcut', 
                    "This keyboard shortcut is already used for this source location.");
            }
        }
    }
    
    /**
     * Validate connection duplication (excluding current record)
     */
    private function validateConnectionDuplication($validator)
    {
        $connectionId = $this->route('route_connection');
        $sourceLocationId = $this->input('source_location_id');
        $targetLocationId = $this->input('target_location_id');
        $sourcePosition = $this->input('source_position');
        
        if ($sourceLocationId && $targetLocationId) {
            $existing = RouteConnection::where('source_location_id', $sourceLocationId)
                                     ->where('target_location_id', $targetLocationId)
                                     ->where('source_position', $sourcePosition)
                                     ->where('id', '!=', $connectionId)
                                     ->first();
                                     
            if ($existing) {
                $validator->errors()->add('target_location_id', 
                    "This connection already exists with the same source position.");
            }
        }
    }
    
    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'source_location_id.required' => '出発地点は必須です。',
            'source_location_id.exists' => '選択された出発地点が存在しません。',
            'target_location_id.required' => '目的地は必須です。',
            'target_location_id.exists' => '選択された目的地が存在しません。',
            'target_location_id.different' => '出発地点と目的地は異なる必要があります。',
            'source_position.between' => '出発位置は0-100の範囲で入力してください。',
            'target_position.between' => '到着位置は0-100の範囲で入力してください。',
            'action_label.in' => '選択されたアクションラベルが無効です。',
            'keyboard_shortcut.in' => '選択されたキーボードショートカットが無効です。'
        ];
    }
}
