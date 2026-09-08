<?php

namespace App\Http\Livewire\Admin;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\HeroiconRegistry;
use Livewire\Component;
use App\Models\ActivityLog;

class ExpenseCategories extends Component
{
    // Color palette unchanged from before.
    public const PALETTE = [
        'amber'   => ['label' => 'Amber',   'value' => 'bg-amber-50 text-amber-700',     'swatch' => 'bg-amber-600'],
        'orange'  => ['label' => 'Orange',  'value' => 'bg-orange-50 text-orange-700',   'swatch' => 'bg-orange-600'],
        'rose'    => ['label' => 'Rose',    'value' => 'bg-rose-50 text-rose-700',       'swatch' => 'bg-rose-600'],
        'pink'    => ['label' => 'Pink',    'value' => 'bg-pink-50 text-pink-700',       'swatch' => 'bg-pink-600'],
        'fuchsia' => ['label' => 'Fuchsia', 'value' => 'bg-fuchsia-50 text-fuchsia-700', 'swatch' => 'bg-fuchsia-600'], // NEW — fills the gap between pink and purple
        'purple'  => ['label' => 'Purple',  'value' => 'bg-purple-50 text-purple-700',   'swatch' => 'bg-purple-600'],
        'indigo'  => ['label' => 'Indigo',  'value' => 'bg-indigo-50 text-indigo-700',   'swatch' => 'bg-indigo-600'],
        'blue'    => ['label' => 'Blue',    'value' => 'bg-blue-50 text-blue-700',       'swatch' => 'bg-blue-600'],
        'cyan'    => ['label' => 'Cyan',    'value' => 'bg-cyan-50 text-cyan-700',       'swatch' => 'bg-cyan-600'],
        'emerald' => ['label' => 'Emerald', 'value' => 'bg-emerald-50 text-emerald-700', 'swatch' => 'bg-emerald-600'],
        'lime'    => ['label' => 'Lime',    'value' => 'bg-lime-50 text-lime-700',       'swatch' => 'bg-lime-600'], // NEW — fills the gap between amber and emerald
        'slate'   => ['label' => 'Slate',   'value' => 'bg-slate-100 text-slate-700',    'swatch' => 'bg-slate-500'],
    ];

    // Quick-pick shortcuts shown as buttons before the admin ever needs to
    // search. Keys are now REAL Heroicon names (except 'food', which is a
    // manual SVG special case) — no translation layer anymore.
    public const CURATED_ICONS = [
        'food'           => 'Food & Dining',
        'truck'          => 'Transportation',
        'academic-cap'   => 'School Supplies',
        'lightning-bolt' => 'Utility',
        'sparkles'       => 'Entertainment',
        'shopping-bag'   => 'Shopping',
        'cash'           => 'Savings',
        'user'           => 'Personal',
        'credit-card'    => 'Bills',
        'document-text'  => 'Default',
    ];

    // --- Create ---
    public $showCreateModal = false;
    public $create_name;
    public $create_description;
    public $create_icon = 'document-text';
    public $create_icon_search = '';
    public $create_color = 'slate';

    // --- Edit ---
    public $editingId = null;
    public $edit_name;
    public $edit_description;
    public $edit_icon;
    public $edit_icon_search = '';
    public $edit_color;

    // --- Delete ---
    public $confirmingDeleteId = null;

    protected function rules($id = null)
    {
        return [
            'name' => 'required|string|max:255|unique:expense_categories,name' . ($id ? ",{$id}" : ''),
            'description' => 'nullable|string|max:255',
            'color' => 'required|string|in:' . implode(',', array_keys(self::PALETTE)),
        ];
    }

    private function iconRule()
    {
        // Valid = 'food' (special-cased) OR any name that actually exists
        // in the installed icon package — covers both curated picks and
        // anything chosen via search.
        return function ($attribute, $value, $fail) {
            if ($value !== 'food' && !HeroiconRegistry::exists($value)) {
                $fail('Please choose a valid icon.');
            }
        };
    }

    // --- Create ---
    public function openCreate()
    {
        $this->reset(['create_name', 'create_description', 'create_icon_search']);
        $this->create_icon = 'document-text';
        $this->create_color = 'slate';
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeCreate()
    {
        $this->showCreateModal = false;
    }

    public function store()
    {
        $this->validate([
            'create_name' => 'required|string|max:255|unique:expense_categories,name',
            'create_description' => 'nullable|string|max:255',
            'create_icon' => ['required', 'string', $this->iconRule()],
            'create_color' => 'required|string|in:' . implode(',', array_keys(self::PALETTE)),
        ]);

        $category = ExpenseCategory::create([
            'name' => $this->create_name,
            'description' => $this->create_description,
            'icon' => $this->create_icon,
            'color' => self::PALETTE[$this->create_color]['value'],
            'status' => 'enabled',
        ]);

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'category_created',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Created expense category \"{$category->name}\" (icon: {$category->icon}, color: {$this->create_color})",
        ]);

        $this->showCreateModal = false;
        session()->flash('success', "\"{$this->create_name}\" was added to your categories.");
    }

    // --- Edit ---
    public function openEdit($id)
    {
        $cat = ExpenseCategory::findOrFail($id);
        $this->editingId = $cat->id;
        $this->edit_name = $cat->name;
        $this->edit_description = $cat->description;
        $this->edit_icon = $cat->icon;
        $this->edit_icon_search = '';
        $this->edit_color = $this->colorKeyFromValue($cat->color);
        $this->resetErrorBag();
    }

    public function closeEdit()
    {
        $this->editingId = null;
    }

    public function update()
    {
        $this->validate([
            'edit_name' => 'required|string|max:255|unique:expense_categories,name,' . $this->editingId,
            'edit_description' => 'nullable|string|max:255',
            'edit_icon' => ['required', 'string', $this->iconRule()],
            'edit_color' => 'required|string|in:' . implode(',', array_keys(self::PALETTE)),
        ]);

        $cat = ExpenseCategory::findOrFail($this->editingId);

        $changes = [];
        if ($cat->name !== $this->edit_name) {
            $changes[] = "name: \"{$cat->name}\" → \"{$this->edit_name}\"";
        }
        if ($cat->description !== $this->edit_description) {
            $changes[] = "description: \"" . ($cat->description ?? '—') . "\" → \"" . ($this->edit_description ?? '—') . "\"";
        }
        if ($cat->icon !== $this->edit_icon) {
            $changes[] = "icon: \"{$cat->icon}\" → \"{$this->edit_icon}\"";
        }
        $newColorValue = self::PALETTE[$this->edit_color]['value'];
        if ($cat->color !== $newColorValue) {
            $changes[] = "color: \"{$this->colorKeyFromValue($cat->color)}\" → \"{$this->edit_color}\"";
        }

        $originalName = $cat->name;

        $cat->update([
            'name' => $this->edit_name,
            'description' => $this->edit_description,
            'icon' => $this->edit_icon,
            'color' => $newColorValue,
        ]);

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'category_edited',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Edited category \"{$originalName}\"" . (!empty($changes) ? ': ' . implode(', ', $changes) : ' — no field changes detected'),
        ]);

        $this->editingId = null;
        session()->flash('success', "\"{$cat->name}\" was updated.");
    }

    // --- Enable / Disable ---
    public function toggleStatus($id)
    {
        $cat = ExpenseCategory::findOrFail($id);
        $wasEnabled = $cat->status !== 'disabled';

        $cat->status = $wasEnabled ? 'disabled' : 'enabled';
        $cat->save();

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => $wasEnabled ? 'category_disabled' : 'category_enabled',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => ($wasEnabled ? 'Disabled' : 'Enabled') . " category \"{$cat->name}\" (ID {$cat->id})",
        ]);

        session()->flash('success', $cat->status === 'disabled'
            ? "\"{$cat->name}\" is now hidden from students."
            : "\"{$cat->name}\" is visible to students again.");
    }

    // --- Delete ---
    public function confirmDelete($id)
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete()
    {
        $this->confirmingDeleteId = null;
    }

    public function delete()
    {
        if (!$this->confirmingDeleteId) {
            return;
        }

        $cat = ExpenseCategory::findOrFail($this->confirmingDeleteId);
        $inUseCount = Expense::where('expense_category_id', $cat->id)->count();

        if ($inUseCount > 0) {
            $this->confirmingDeleteId = null;
            session()->flash('error', "Can't delete \"{$cat->name}\" — it's used by {$inUseCount} logged " . str('expense')->plural($inUseCount) . ". Disable it instead to hide it from new entries.");
            return;
        }

        $name = $cat->name;
        $catId = $cat->id;
        $cat->delete();

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'category_deleted',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Permanently deleted category \"{$name}\" (ID {$catId})",
        ]);

        $this->confirmingDeleteId = null;
        session()->flash('success', "\"{$name}\" was permanently deleted.");
    }

    private function colorKeyFromValue($value)
    {
        foreach (self::PALETTE as $key => $preset) {
            if ($preset['value'] === $value) {
                return $key;
            }
        }
        return 'slate';
    }

    private function findDuplicateCombo($icon, $colorKey, $excludeId = null)
    {
        if ($icon === 'document-text' && $colorKey === 'slate') {
            return null;
        }

        $colorValue = self::PALETTE[$colorKey]['value'] ?? null;
        if (!$colorValue) {
            return null;
        }

        return ExpenseCategory::where('icon', $icon)
            ->where('color', $colorValue)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }

    public function render()
    {
        $categories = ExpenseCategory::orderBy('name')->get();

        $createDuplicate = $this->showCreateModal
            ? $this->findDuplicateCombo($this->create_icon, $this->create_color)
            : null;

        $editDuplicate = $this->editingId
            ? $this->findDuplicateCombo($this->edit_icon, $this->edit_color, $this->editingId)
            : null;

        return view('livewire.admin.expense-categories', [
            'categories' => $categories,
            'palette' => self::PALETTE,
            'curatedIcons' => self::CURATED_ICONS,
            'createSearchResults' => HeroiconRegistry::search($this->create_icon_search),
            'editSearchResults' => HeroiconRegistry::search($this->edit_icon_search),
            'createDuplicate' => $createDuplicate,
            'editDuplicate' => $editDuplicate,
        ])->layout('layouts.admin');
    }
}