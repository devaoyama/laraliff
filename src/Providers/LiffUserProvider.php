<?php

namespace Devkeita\Laraliff\Providers;

use Illuminate\Auth\EloquentUserProvider;

class LiffUserProvider extends EloquentUserProvider
{
    public function retrieveByLiffId($id)
    {
        return $this->createModel()->newQuery()
            ->where('liff_id', $id)
            ->first();
    }
}
