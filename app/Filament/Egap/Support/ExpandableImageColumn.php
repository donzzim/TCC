<?php

namespace App\Filament\Egap\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Coluna de imagem que abre a imagem ampliada em um modal ao ser clicada.
 */
class ExpandableImageColumn extends ImageColumn
{
    protected string | Closure | null $expandModalHeading = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->circular()
            ->alignCenter()
            ->tooltip('Clique para ampliar')
            ->action($this->expandAction());
    }

    /**
     * Título do modal de imagem ampliada. Por padrão, usa o label da coluna.
     */
    public function expandModalHeading(string | Closure | null $heading): static
    {
        $this->expandModalHeading = $heading;

        return $this;
    }

    protected function expandAction(): Action
    {
        return Action::make('expandir_'.Str::snake(str_replace('.', '_', $this->getName())))
            ->label('Imagem ampliada')
            ->modalHeading(fn (Model $record): ?string => $this->expandModalHeading !== null
                ? $this->evaluate($this->expandModalHeading, ['record' => $record], [Model::class => $record])
                : $this->getLabel())
            ->modalContent(fn (Model $record): HtmlString => $this->expandedImagesHtml($record))
            ->modalWidth(Width::Large)
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }

    protected function expandedImagesHtml(Model $record): HtmlString
    {
        $defaultUrl = $this->getDefaultImageUrl();

        $urls = collect(Arr::wrap($this->record($record)->getState()))
            ->filter(fn (mixed $state): bool => filled($state))
            ->map(fn (string $state): ?string => $this->getImageUrl($state) ?? $defaultUrl)
            ->filter()
            ->whenEmpty(fn ($urls) => $urls->push($defaultUrl))
            ->filter();

        $onError = filled($defaultUrl)
            ? ' onerror="this.onerror=null;this.src=\''.e($defaultUrl).'\'"'
            : '';

        return new HtmlString($urls
            ->map(fn (string $url): string => '<img src="'.e($url).'" class="mx-auto max-h-[70vh] w-auto rounded-lg" alt=""'.$onError.' />')
            ->implode('<div class="h-4"></div>'));
    }
}
