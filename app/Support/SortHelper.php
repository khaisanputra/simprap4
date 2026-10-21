<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

class SortHelper
{
    /**
     * Membuat URL sorting yang aman untuk tabel, mempertahankan
     * filter pencarian & role yang sedang aktif.
     */
    public static function sortUrl(string $column, string $sort, string $order): string
    {
        $newOrder = ($column === $sort && $order === 'asc') ? 'desc' : 'asc';

        return Request::fullUrlWithQuery([
            'sort'  => $column,
            'order' => $newOrder,
        ]);
    }
}
