<?php

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Catalog\Actions\DuplicateProduct;
use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Actions\RejectProduct;
use App\Domain\Catalog\Actions\SchedulePublication;
use App\Domain\Catalog\Actions\SubmitProductForReview;
use App\Domain\Catalog\Actions\UnpublishProduct;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Filament\Support\DomainAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Product actions shared by the product list and the edit page.
 * Each one calls a domain Action, so the rules are the same as in the API.
 */
final class ProductActions
{
    public static function publish(): Action
    {
        return Action::make('publish')
            ->label('Publicar')
            ->icon(Heroicon::OutlinedEye)
            ->color('success')
            ->authorize('publish')
            ->visible(fn (Product $record) => ! $record->isPublished() && ! $record->trashed())
            ->requiresConfirmation()
            ->modalHeading('Publicar producto')
            ->modalDescription('El producto será visible para los clientes en la tienda.')
            ->label(fn (Product $record) => $record->status === ProductStatus::PendingReview ? 'Aprobar y publicar' : 'Publicar')
            ->action(fn (Product $record) => DomainAction::run(
                fn () => app(PublishProduct::class)->handle($record, auth()->user()),
                success: 'Producto publicado',
                failureTitle: 'Todavía no se puede publicar',
            ));
    }

    public static function submitForReview(): Action
    {
        return Action::make('submitForReview')
            ->label('Enviar a revisión')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->authorize('submitForReview')
            ->visible(fn (Product $record) => $record->status === ProductStatus::Draft && ! $record->trashed())
            ->requiresConfirmation()
            ->modalDescription('Un responsable revisará el producto antes de publicarlo. Te avisaremos.')
            ->action(fn (Product $record) => DomainAction::run(
                fn () => app(SubmitProductForReview::class)->handle($record),
                success: 'Enviado a revisión',
                failureTitle: 'Todavía no se puede enviar',
            ));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Devolver con comentarios')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->authorize('publish')
            ->visible(fn (Product $record) => $record->status === ProductStatus::PendingReview)
            ->modalHeading('Devolver al colaborador')
            ->schema([
                Textarea::make('note')
                    ->label('¿Qué tiene que cambiar?')
                    ->placeholder('Ej.: La foto principal está borrosa; añade las medidas en la descripción.')
                    ->required()
                    ->rows(3),
            ])
            ->action(fn (Product $record, array $data) => DomainAction::run(
                fn () => app(RejectProduct::class)->handle($record, auth()->user(), $data['note']),
                success: 'Producto devuelto al colaborador',
            ));
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish')
            ->label('Ocultar')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('gray')
            ->authorize('publish')
            ->visible(fn (Product $record) => $record->isPublished())
            ->requiresConfirmation()
            ->modalHeading('Ocultar producto')
            ->modalDescription('Los clientes dejarán de verlo. Podrás publicarlo de nuevo cuando quieras.')
            ->action(fn (Product $record) => DomainAction::run(
                fn () => app(UnpublishProduct::class)->handle($record),
                success: 'Producto ocultado',
            ));
    }

    /** CAT-12: publish automatically at a chosen moment (Spanish time). */
    public static function schedule(): Action
    {
        return Action::make('schedulePublication')
            ->label(fn (Product $record) => $record->scheduled_publish_at ? 'Cambiar programación' : 'Programar publicación')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->authorize('publish')
            ->visible(fn (Product $record) => ! $record->isPublished() && ! $record->trashed())
            ->modalHeading('Programar publicación')
            ->modalDescription('El producto se publicará solo en la fecha y hora elegidas (hora de España). Comprobamos ahora que esté listo para vender.')
            ->fillForm(fn (Product $record) => ['at' => $record->scheduled_publish_at])
            ->schema([
                DateTimePicker::make('at')
                    ->label('Publicar el')
                    ->seconds(false)
                    ->minDate(now())
                    ->required(),
            ])
            ->action(fn (Product $record, array $data) => DomainAction::run(
                fn () => app(SchedulePublication::class)->handle($record, Carbon::parse($data['at'])),
                success: 'Publicación programada',
                failureTitle: 'No se puede programar todavía',
            ));
    }

    public static function cancelSchedule(): Action
    {
        return Action::make('cancelSchedule')
            ->label('Cancelar programación')
            ->icon(Heroicon::OutlinedXMark)
            ->color('gray')
            ->authorize('publish')
            ->visible(fn (Product $record) => $record->scheduled_publish_at !== null && ! $record->isPublished())
            ->requiresConfirmation()
            ->action(fn (Product $record) => DomainAction::run(
                fn () => app(SchedulePublication::class)->handle($record, null),
                success: 'Programación cancelada',
            ));
    }

    public static function duplicate(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicar')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->authorize('replicate')
            ->visible(fn (Product $record) => ! $record->trashed())
            ->requiresConfirmation()
            ->modalHeading('Duplicar producto')
            ->modalDescription('Se creará una copia en borrador con las mismas variantes y categorías (sin stock ni fotos).')
            ->action(function (Product $record, $livewire): void {
                $copy = null;
                $ok = DomainAction::run(
                    function () use ($record, &$copy): void {
                        $copy = app(DuplicateProduct::class)->handle($record->loadMissing('variants'));
                    },
                    success: 'Copia creada',
                );

                if ($ok && $copy) {
                    $livewire->redirect(ProductResource::getUrl('edit', ['record' => $copy]));
                }
            });
    }

    public static function adjustStock(): Action
    {
        return Action::make('adjustStock')
            ->label('Ajustar stock')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('warning')
            ->authorize('adjustStock')
            ->visible(fn (Product $record) => ! $record->trashed())
            ->modalHeading('Ajustar stock')
            ->modalDescription('Usa un número positivo para sumar unidades y negativo para restarlas.')
            ->modalSubmitActionLabel('Guardar ajuste')
            ->schema(fn (Product $record) => [
                Select::make('variant_id')
                    ->label('Variante')
                    ->options($record->variants()->get()->mapWithKeys(fn (ProductVariant $v) => [
                        $v->id => trim(($v->name ?: 'Única')." · {$v->sku} · stock actual: {$v->stock}"),
                    ]))
                    ->default($record->variants()->value('id'))
                    ->required(),
                TextInput::make('change')
                    ->label('Cantidad')
                    ->helperText('Ejemplo: 10 para añadir diez unidades, -2 para quitar dos.')
                    ->integer()
                    ->required()
                    ->notIn([0]),
                Select::make('reason')
                    ->label('Motivo')
                    ->options([
                        'Recepción de mercancía' => 'Recepción de mercancía',
                        'Recuento de inventario' => 'Recuento de inventario',
                        'Producto dañado o perdido' => 'Producto dañado o perdido',
                        'Devolución de cliente' => 'Devolución de cliente',
                        'Otro' => 'Otro',
                    ])
                    ->required(),
            ])
            ->action(function (Product $record, array $data, $livewire): void {
                $variant = $record->variants()->whereKey($data['variant_id'])->firstOrFail();

                $ok = DomainAction::run(
                    fn () => app(AdjustStock::class)->handle($variant, (int) $data['change'], $data['reason']),
                    success: 'Stock actualizado',
                    failureTitle: 'No se ha ajustado el stock',
                );

                if ($ok && $livewire instanceof Pages\EditProduct) {
                    $livewire->redirect(ProductResource::getUrl('edit', ['record' => $record]));
                }
            });
    }
}
