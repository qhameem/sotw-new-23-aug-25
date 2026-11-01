<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class PathPaginator extends LengthAwarePaginator
{
    public function url($page)
    {
        $page = max(1, (int) $page);
        $url = rtrim($this->path(), '/').($page > 1 ? '/page/'.$page : '');
        $query = Arr::query($this->query);

        return $url.($query !== '' ? '?'.$query : '').$this->buildFragment();
    }
}
