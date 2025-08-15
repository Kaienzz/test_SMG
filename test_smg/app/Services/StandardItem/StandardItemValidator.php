<?php

namespace App\Services\StandardItem;

class StandardItemValidator
{
    private array $errors = [];
    
    public function validate(array $data): bool
    {
        $this->errors = [];
        
        $this->validateRootStructure($data);
        
        if (isset($data['items']) && is_array($data['items'])) {
            $this->validateItems($data['items']);
        }
        
        return empty($this->errors);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function validateAndThrow(array $data): void
    {
        if (!$this->validate($data)) {
            throw new StandardItemValidationException($this->errors);
        }
    }
    
    private function validateRootStructure(array $data): void
    {
        // スキーマバージョンチェック
        if (!isset($data['schema_version'])) {
            $this->errors[] = 'schema_version is required';
        } elseif (!is_string($data['schema_version'])) {
            $this->errors[] = 'schema_version must be a string';
        }
        
        // アイテムリストチェック
        if (!isset($data['items'])) {
            $this->errors[] = 'items array is required';
        } elseif (!is_array($data['items'])) {
            $this->errors[] = 'items must be an array';
        }
        
        // オプショナルフィールドの型チェック
        if (isset($data['last_updated']) && !is_string($data['last_updated'])) {
            $this->errors[] = 'last_updated must be a string';
        }
        
        if (isset($data['description']) && !is_string($data['description'])) {
            $this->errors[] = 'description must be a string';
        }
    }
    
    private function validateItems(array $items): void
    {
        foreach ($items as $index => $item) {
            $this->validateItem($item, $index);
        }
    }
    
    private function validateItem(array $item, int $index): void
    {
        $prefix = "Item[{$index}]";
        
        // 必須フィールドの検証
        $requiredFields = [
            'id' => 'string',
            'name' => 'string',
            'description' => 'string',
            'category' => 'string',
            'category_name' => 'string',
            'effects' => 'array',
            'value' => 'integer',
            'is_equippable' => 'boolean',
            'is_usable' => 'boolean',
            'is_standard' => 'boolean',
        ];
        
        foreach ($requiredFields as $field => $type) {
            if (!array_key_exists($field, $item)) {
                $this->errors[] = "{$prefix}.{$field} is required";
                continue;
            }
            
            if (!$this->validateType($item[$field], $type)) {
                $this->errors[] = "{$prefix}.{$field} must be of type {$type}";
            }
        }
        
        // オプショナルフィールドの型検証
        $optionalFields = [
            'sell_price' => 'integer',
            'stack_limit' => ['integer', 'null'],
            'max_durability' => ['integer', 'null'],
            'weapon_type' => ['string', 'null'],
        ];
        
        foreach ($optionalFields as $field => $types) {
            if (array_key_exists($field, $item)) {
                $types = is_array($types) ? $types : [$types];
                $isValid = false;
                
                foreach ($types as $type) {
                    if ($this->validateType($item[$field], $type)) {
                        $isValid = true;
                        break;
                    }
                }
                
                if (!$isValid) {
                    $typesStr = implode(' or ', $types);
                    $this->errors[] = "{$prefix}.{$field} must be of type {$typesStr}";
                }
            }
        }
        
        // 値の範囲チェック
        if (isset($item['value']) && is_int($item['value']) && $item['value'] < 0) {
            $this->errors[] = "{$prefix}.value must be non-negative";
        }
        
        if (isset($item['sell_price']) && is_int($item['sell_price']) && $item['sell_price'] < 0) {
            $this->errors[] = "{$prefix}.sell_price must be non-negative";
        }
        
        if (isset($item['stack_limit']) && is_int($item['stack_limit']) && $item['stack_limit'] <= 0) {
            $this->errors[] = "{$prefix}.stack_limit must be positive";
        }
        
        if (isset($item['max_durability']) && is_int($item['max_durability']) && $item['max_durability'] <= 0) {
            $this->errors[] = "{$prefix}.max_durability must be positive";
        }
        
        // IDフォーマットチェック
        if (isset($item['id']) && is_string($item['id'])) {
            if (!preg_match('/^std_\d+$/', $item['id'])) {
                $this->errors[] = "{$prefix}.id must follow format 'std_X' where X is a number";
            }
        }
        
        // カテゴリチェック
        if (isset($item['category']) && is_string($item['category'])) {
            $validCategories = [
                'potion', 'weapon', 'body_equipment', 'foot_equipment', 
                'shield', 'head_equipment', 'accessory', 'material', 'bag'
            ];
            
            if (!in_array($item['category'], $validCategories)) {
                $this->errors[] = "{$prefix}.category must be one of: " . implode(', ', $validCategories);
            }
        }
        
        // 武器タイプチェック
        if (isset($item['weapon_type']) && is_string($item['weapon_type'])) {
            $validWeaponTypes = ['physical', 'magical'];
            
            if (!in_array($item['weapon_type'], $validWeaponTypes)) {
                $this->errors[] = "{$prefix}.weapon_type must be one of: " . implode(', ', $validWeaponTypes);
            }
        }
        
    }
    
    private function validateType($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'integer':
                return is_int($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'null':
                return is_null($value);
            default:
                return false;
        }
    }
}