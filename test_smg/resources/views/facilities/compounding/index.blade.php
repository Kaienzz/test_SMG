@php
    $services = $facilityData['services']['compounding'] ?? [];
    $recipes = $services['recipes'] ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $facilityType->getIcon() }} {{ $facilityType->getDisplayName() }}（{{ $currentLocation['name'] }}）
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="mb-4">材料から消耗品などを調合します。スキル『調合』のレベルとSPを消費します。</p>

                    @if(empty($recipes))
                        <div class="text-gray-500">この町では利用できるレシピがありません。</div>
                    @else
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-600">
                                    <th class="text-left p-2">レシピ</th>
                                    <th class="text-left p-2">成果物</th>
                                    <th class="text-left p-2">必要Lv</th>
                                    <th class="text-left p-2">成功率</th>
                                    <th class="text-left p-2">SP</th>
                                    <th class="text-left p-2">材料</th>
                                    <th class="text-left p-2">数量</th>
                                    <th class="text-left p-2">実行</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($recipes as $r)
                                <tr class="border-b border-gray-700">
                                    <td class="p-2">{{ $r['name'] }}</td>
                                    <td class="p-2">{{ $r['product']['name'] ?? '不明' }} × {{ $r['product_quantity'] }}</td>
                                    <td class="p-2">{{ $r['required_skill_level'] }}</td>
                                    <td class="p-2">{{ $r['success_rate'] }}%</td>
                                    <td class="p-2">{{ $r['sp_cost'] }}</td>
                                    <td class="p-2">
                                        @foreach($r['ingredients'] as $ing)
                                            <div>{{ $ing['item']['name'] ?? '不明' }} × {{ $ing['quantity'] }}</div>
                                        @endforeach
                                    </td>
                                    <td class="p-2">
                                        <input type="number" min="1" max="999" value="1" class="w-20 p-1 bg-gray-700 border border-gray-600 rounded" id="qty-{{ $r['id'] }}">
                                    </td>
                                    <td class="p-2">
                                        <button class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded" onclick="compound({{ $r['id'] }})">調合</button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif

                    <div id="result" class="mt-4 text-sm"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function compound(recipeId) {
            const qty = document.getElementById('qty-' + recipeId).value || 1;
            const res = await fetch("{{ route('facilities.compounding.transaction') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ recipe_id: recipeId, quantity: parseInt(qty, 10) })
            });
            const data = await res.json();
            const el = document.getElementById('result');
            if (data.success) {
                el.innerHTML = `<div class='text-green-400'>${data.message}<br>成功:${data.crafted.success_count} 失敗:${data.crafted.fail_count} 生成:${data.crafted.added_quantity} 消費SP:${data.sp_spent} 獲得EXP:${data.exp_gain}</div>`;
            } else {
                el.innerHTML = `<div class='text-red-400'>${data.message}</div>`;
            }
        }
    </script>
</x-app-layout>
