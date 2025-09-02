<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\CompoundingRecipeRequest;
use App\Services\Admin\AdminAuditService;
use App\Services\Admin\AdminCompoundingService;
use Illuminate\Http\Request;

class AdminCompoundingRecipeController extends AdminController
{
    private AdminCompoundingService $service;

    public function __construct(AdminAuditService $auditService, AdminCompoundingService $service)
    {
        parent::__construct($auditService);
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.view'); // reuse items permission for Phase1
        $this->trackPageAccess('admin.compounding.recipes.index');

        $filters = [
            'search' => $request->get('search'),
            'active' => $request->get('active')
        ];
        $recipes = $this->service->listRecipes($filters);
        return view('admin.compounding.recipes.index', compact('recipes', 'filters'));
    }

    public function create()
    {
        $this->initializeForRequest();
        $this->checkPermission('items.edit');
        $items = $this->service->getItemsForSelect();
        return view('admin.compounding.recipes.create', compact('items'));
    }

    public function store(CompoundingRecipeRequest $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.edit');
        $data = $request->validated();
        $recipe = $this->service->create($data);
        $this->auditLog('compounding.recipes.created', ['id' => $recipe->id, 'name' => $recipe->name]);
        return redirect()->route('admin.compounding.recipes.edit', $recipe->id)->with('success', 'レシピを作成しました。');
    }

    public function edit(int $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.edit');
        $recipe = $this->service->getRecipe($id);
        if (!$recipe) return redirect()->route('admin.compounding.recipes.index')->with('error', 'レシピが見つかりません。');
        $items = $this->service->getItemsForSelect();
        return view('admin.compounding.recipes.edit', compact('recipe', 'items'));
    }

    public function update(CompoundingRecipeRequest $request, int $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.edit');
        $data = $request->validated();
        $recipe = $this->service->update($id, $data);
        if (!$recipe) return redirect()->route('admin.compounding.recipes.index')->with('error', 'レシピが見つかりません。');
        $this->auditLog('compounding.recipes.updated', ['id' => $recipe->id, 'name' => $recipe->name]);
        return redirect()->route('admin.compounding.recipes.edit', $recipe->id)->with('success', 'レシピを更新しました。');
    }

    public function destroy(int $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('items.delete');
        $ok = $this->service->delete($id);
        if ($ok) {
            $this->auditLog('compounding.recipes.deleted', ['id' => $id]);
            return redirect()->route('admin.compounding.recipes.index')->with('success', 'レシピを削除しました。');
        }
        return redirect()->route('admin.compounding.recipes.index')->with('error', 'レシピの削除に失敗しました。');
    }
}
