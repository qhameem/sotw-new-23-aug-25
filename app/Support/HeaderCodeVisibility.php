<?php

namespace App\Support;

use Illuminate\Http\Request;

class HeaderCodeVisibility
{
    public function shouldRender(Request $request): bool
    {
        if ($request->routeIs('admin.*', 'products.create')) {
            return false;
        }

        $path = trim($request->path(), '/');

        return $path !== 'add-product'
            && $path !== 'admin'
            && ! str_starts_with($path, 'admin/');
    }
}
