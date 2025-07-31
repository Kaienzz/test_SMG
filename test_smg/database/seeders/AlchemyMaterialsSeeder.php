<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AlchemyMaterial;

class AlchemyMaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存データをクリア
        AlchemyMaterial::truncate();
        
        // 基本素材データを取得してシード
        $materialsData = AlchemyMaterial::getBasicMaterialsData();
        
        foreach ($materialsData as $materialData) {
            AlchemyMaterial::create($materialData);
        }
        
        $this->command->info('錬金素材データの投入が完了しました。');
    }
}
