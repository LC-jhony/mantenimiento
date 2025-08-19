<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\Column;

class ProgressBrakeColumn extends Column
{
    protected string $view = 'tables.columns.progress-brake-column';
    protected $progressCallback;

    public function progress(callable $callback): static
    {
        $this->progressCallback = $callback;
        return $this;
    }

    public function getState(): mixed
    {
        if ($this->progressCallback) {
            return call_user_func($this->progressCallback, $this->getRecord());
        }
        return parent::getState();
    }
}
