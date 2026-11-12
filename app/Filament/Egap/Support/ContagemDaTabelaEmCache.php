<?php

namespace App\Filament\Egap\Support;

use Filament\Tables\Enums\PaginationMode;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait ContagemDaTabelaEmCache
{
    protected int $segundosDaContagemEmCache = 60;

    protected function paginateTableQuery(Builder $query): Paginator | CursorPaginator
    {
        if ($this->getTable()->getPaginationMode() !== PaginationMode::Default) {
            return parent::paginateTableQuery($query);
        }

        $base = $query->toBase();
        $consulta = $base->cloneWithout(['orders', 'limit', 'offset']);
        $chave = 'egap_contagem_tabela:' . static::class . ':' . md5($consulta->toSql() . serialize($consulta->getBindings()));

        $total = Cache::store(config('cache.egap_store'))->remember(
            $chave,
            $this->segundosDaContagemEmCache,
            fn (): int => $base->getCountForPagination(),
        );

        $perPage = $this->getTableRecordsPerPage();

        return $query->paginate(
            perPage: ($perPage === 'all') ? $total : $perPage,
            pageName: $this->getTablePaginationPageName(),
            total: $total,
        )->onEachSide(0);
    }
}
