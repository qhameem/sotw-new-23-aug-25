<?php

namespace App\Services;

use App\Models\StartupDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StartupDirectoryCatalog
{
    public const SORTS = [
        'dr' => 'Highest DR',
        'dr_asc' => 'Lowest DR',
        'name' => 'A–Z',
        'name_desc' => 'Z–A',
    ];

    public function forRequest(Request $request): array
    {
        $query = StartupDirectory::query();
        $directoryCount = $query->count();
        $search = $request->query('q');
        $search = is_string($search) ? Str::limit(trim($search), 100, '') : '';
        $filters = $search !== '' ? ['q' => $search] : [];

        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $query->where(fn ($nested) => $nested->whereLike('name', $pattern)
                ->orWhereLike('website_url', $pattern));
        }

        foreach ([
            'category' => StartupDirectory::CATEGORIES,
            'pricing' => StartupDirectory::PRICING + ['unknown' => 'Unknown'],
            'link_type' => StartupDirectory::LINK_TYPES + ['unknown' => 'Unknown'],
        ] as $column => $options) {
            $value = $request->query($column);
            if (!is_string($value) || !isset($options[$value])) {
                continue;
            }

            $filters[$column] = $value;
            $value === 'unknown' ? $query->whereNull($column) : $query->where($column, $value);
        }

        $sort = $request->query('sort');
        $sort = is_string($sort) && isset(self::SORTS[$sort]) ? $sort : 'dr';
        if ($sort !== 'dr') {
            $filters['sort'] = $sort;
        }

        match ($sort) {
            'dr' => $query->orderByRaw('dr IS NULL')->orderByDesc('dr'),
            'dr_asc' => $query->orderByRaw('dr IS NULL')->orderBy('dr'),
            'name_desc' => $query->orderByDesc('name'),
            'name' => $query,
        };

        return [
            'directories' => $query->orderBy('name')->orderBy('id')->paginate(50)->appends($filters),
            'directoryFilters' => $filters,
            'directoryCount' => $directoryCount,
        ];
    }
}
