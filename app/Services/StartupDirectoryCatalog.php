<?php

namespace App\Services;

use App\Models\StartupDirectory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class StartupDirectoryCatalog
{
    public const PAGE_SIZES = [50 => '50', 100 => '100', 250 => '250', 500 => '500', 'all' => 'All'];

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
        $drUpdatedAt = $query->max('dr_updated_at');
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

        $pageSize = $request->query('per_page');
        $pageSize = is_string($pageSize) && isset(self::PAGE_SIZES[$pageSize]) ? $pageSize : '50';
        if ($pageSize !== '50') {
            $filters['per_page'] = $pageSize;
        }

        $query->orderBy('name')->orderBy('id');
        if ($pageSize === 'all') {
            $rows = $query->get();
            $directories = new LengthAwarePaginator($rows, $rows->count(), max(1, $rows->count()), 1, [
                'path' => $request->url(),
            ]);
        } else {
            $directories = $query->paginate((int) $pageSize);
        }

        return [
            'directories' => $directories->appends($filters),
            'directoryFilters' => $filters,
            'directoryCount' => $directoryCount,
            'directoryDrUpdatedAt' => $drUpdatedAt === null ? null : Carbon::parse($drUpdatedAt),
        ];
    }
}
