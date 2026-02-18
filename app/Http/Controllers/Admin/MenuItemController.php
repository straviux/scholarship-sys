<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class MenuItemController extends Controller
{
    /**
     * Check if user is an administrator
     */
    private function checkAdmin()
    {
        if (!auth()->check() || !auth()->user()->hasRole('administrator')) {
            abort(403, 'Unauthorized access to menu items');
        }
    }

    /**
     * Display a listing of menu items
     */
    public function index()
    {
        $this->checkAdmin();

        $menus = MenuItem::orderBy('order')
            ->with('children')
            ->get();

        $permissions = Permission::orderBy('name')->pluck('name', 'id');

        return Inertia::render('Admin/MenuManagement/Index', [
            'menus' => $menus,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Get menus as JSON API response
     */
    public function apiIndex()
    {
        $this->checkAdmin();

        return MenuItem::orderBy('order')
            ->with('children')
            ->get();
    }

    /**
     * Store a newly created menu item
     */
    public function store(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'permission' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'order' => 'required|integer|min:0',
            'parent_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
            'is_group' => 'boolean',
            'description' => 'nullable|string|max:500',
        ]);

        MenuItem::create($validated);

        return redirect()->route('admin.menu-items.index')
            ->with('success', 'Menu item created successfully');
    }

    /**
     * Update the specified menu item
     */
    public function update(Request $request, MenuItem $menuItem)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'permission' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'order' => 'required|integer|min:0',
            'parent_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
            'is_group' => 'boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $menuItem->update($validated);

        return redirect()->route('admin.menu-items.index')
            ->with('success', 'Menu item updated successfully');
    }

    /**
     * Delete the specified menu item
     */
    public function destroy(MenuItem $menuItem)
    {
        $this->checkAdmin();

        // Move children to parent before deleting
        $menuItem->children()->update(['parent_id' => $menuItem->parent_id]);

        $menuItem->delete();

        return redirect()->route('admin.menu-items.index')
            ->with('success', 'Menu item deleted successfully');
    }

    /**
     * Get available icons (for icon picker)
     */
    public function getIcons()
    {
        $icons = [
            'pi pi-home' => 'Home',
            'pi pi-chart-bar' => 'Dashboard',
            'pi pi-file' => 'Documents and Forms',
            'pi pi-graduation-cap' => 'Scholarship',
            'pi pi-clipboard' => 'Waiting List',
            'pi pi-check-circle' => 'Reviewed',
            'pi pi-users' => 'Profiles',
            'pi pi-credit-card' => 'Vouchers',
            'pi pi-bell' => 'Updates',
            'pi pi-question-circle' => 'Help',
            'pi pi-table' => 'Library',
            'pi pi-book' => 'Programs',
            'pi pi-list' => 'Requirements',
            'pi pi-building' => 'Schools',
            'pi pi-code' => 'Responsibility Centers',
            'pi pi-sliders-h' => 'Options',
            'pi pi-shield' => 'Administrator',
            'pi pi-lock' => 'Access Control',
            'pi pi-trash' => 'Deleted Records',
            'pi pi-download' => 'Data Export',
            'pi pi-megaphone' => 'Announcements',
            'pi pi-cog' => 'Settings',
            'pi pi-folder' => 'Folder',
            'pi pi-inbox' => 'Inbox',
            'pi pi-search' => 'Search',
            'pi pi-filter' => 'Filter',
            'pi pi-star' => 'Favorites',
            'pi pi-heart' => 'Liked',
            'pi pi-thumbs-up' => 'Thumbs Up',
            'pi pi-comments' => 'Comments',
            'pi pi-share-alt' => 'Share',
            'pi pi-upload' => 'Upload',
        ];

        return response()->json($icons);
    }

    /**
     * Reorder menu items via AJAX
     */
    public function reorder(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menu_items,id',
            'menus.*.order' => 'required|integer|min:0',
            'menus.*.parent_id' => 'nullable|exists:menu_items,id',
        ]);

        foreach ($validated['menus'] as $menu) {
            MenuItem::where('id', $menu['id'])->update([
                'order' => $menu['order'],
                'parent_id' => $menu['parent_id']
            ]);
        }

        // Clear menu cache
        \Illuminate\Support\Facades\Cache::forget('menu.main');
        \Illuminate\Support\Facades\Cache::forget('menu.all');

        return response()->json(['success' => true]);
    }
}
