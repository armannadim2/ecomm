@extends('store.account.layout')

{{--
    New return/exchange request (Stitch "Gestió de Devolucions i Canvis", steps 1-2; the return is always brought to the shop).
    Variables: $orders (delivered orders with units left), $selected (?Order), $available (order item id => units left), $withdrawalDeadline
--}}

@section('title', __('Nueva solicitud'))

@php
    use App\Domain\Orders\Enums\ReturnReason;
    use App\Domain\Orders\Enums\ReturnType;
    use App\Support\Money;
    $date = fn ($d) => $d?->timezone('Europe/Madrid')->locale(app()->getLocale())->isoFormat('LL');
    $field = 'mt-1 w-full rounded-xl border border-outline-variant bg-white px-3 py-2.5 text-sm text-primary focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/30';
@endphp

@section('account')
    <a href="{{ route('account.returns') }}" class="text-sm font-semibold text-secondary underline-offset-2 hover:underline">← {{ __('Devoluciones y cambios') }}</a>
    <h1 class="mt-3 text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Nueva solicitud') }}</h1>

    @if (! $selected)
        <p class="mt-4 text-sm text-on-surface-variant">{{ __('No tienes pedidos entregados que se puedan devolver o cambiar.') }}</p>
    @else
        @error('request')
            <p role="alert" class="mt-4 rounded-2xl bg-error-container px-4 py-3 text-sm text-on-error-container">{{ $message }}</p>
        @enderror

        <form method="post" action="{{ route('account.returns.store') }}" class="mt-6 flex flex-col gap-8">
            @csrf
            <input type="hidden" name="order" value="{{ $selected->ulid }}">

            {{-- 1. Order and items --}}
            <section aria-labelledby="step-items">
                <h2 id="step-items" class="flex items-center gap-3 text-lg font-bold text-primary">
                    <span class="flex size-8 items-center justify-center rounded-full bg-primary text-sm text-white">1</span>
                    {{ __('Elige el pedido y los artículos') }}
                </h2>

                @if ($orders->count() > 1)
                    <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="{{ __('Pedido') }}">
                        @foreach ($orders as $order)
                            <a href="{{ route('account.returns.create', ['order' => $order->ulid]) }}" @if ($order->is($selected)) aria-current="true" @endif
                               @class(['rounded-full px-4 py-2 text-xs font-bold transition-colors',
                                   'bg-primary text-white' => $order->is($selected),
                                   'bg-surface-container-low text-primary hover:bg-surface-container' => ! $order->is($selected)])>
                                {{ $order->number }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <p class="mt-3 text-sm text-on-surface-variant">
                    {{ __('Pedido :number, entregado el :date.', ['number' => $selected->number, 'date' => $date($selected->completed_at)]) }}
                    @if ($withdrawalDeadline?->isFuture())
                        {{ __('Puedes devolverlo sin dar motivo hasta el :date.', ['date' => $date($withdrawalDeadline)]) }}
                    @else
                        {{ __('El plazo de 14 días ya ha terminado: solo puedes reclamar la garantía legal (producto defectuoso o equivocado).') }}
                    @endif
                </p>

                <ul class="mt-3 divide-y divide-outline-variant/60 rounded-2xl border border-outline-variant/60">
                    @foreach ($selected->items as $item)
                        @php $left = $available[$item->id] ?? 0; @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-primary">{{ $item->product_name }}</span>
                                @if ($item->variant_name)<span class="block text-xs text-on-surface-variant">{{ $item->variant_name }}</span>@endif
                                <span class="block text-xs text-on-surface-variant">{{ Money::format($item->unit_price_cents) }} · {{ __('Pediste :count', ['count' => $item->quantity]) }}</span>
                            </span>
                            @if ($left > 0)
                                <label class="flex items-center gap-2 text-xs font-bold text-on-surface-variant">
                                    {{ __('Unidades a devolver') }}
                                    <input type="number" name="quantities[{{ $item->id }}]" min="0" max="{{ $left }}" value="{{ old('quantities.'.$item->id, 0) }}"
                                           class="w-20 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-primary focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/30">
                                    <span class="font-normal">/ {{ $left }}</span>
                                </label>
                            @else
                                <span class="text-xs font-semibold text-on-surface-variant">{{ __('Ya solicitado') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @error('quantities')<p role="alert" class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
            </section>

            {{-- 2. Type and reason --}}
            <section aria-labelledby="step-type">
                <h2 id="step-type" class="flex items-center gap-3 text-lg font-bold text-primary">
                    <span class="flex size-8 items-center justify-center rounded-full bg-primary text-sm text-white">2</span>
                    {{ __('¿Qué quieres hacer y por qué?') }}
                </h2>
                <fieldset class="mt-3 grid gap-3 sm:grid-cols-2">
                    <legend class="sr-only">{{ __('Tipo de solicitud') }}</legend>
                    @foreach ([[ReturnType::Return, __('Devolución'), __('Te devolvemos el importe de los artículos con el mismo medio de pago que usaste.')], [ReturnType::Exchange, __('Cambio'), __('Cambias el artículo por otro en la tienda. Si hay diferencia de precio, se ajusta en el mostrador.')]] as [$type, $label, $text])
                        <label class="flex cursor-pointer gap-3 rounded-2xl border border-outline-variant/60 p-4 has-[:checked]:border-secondary has-[:checked]:bg-secondary-container/40">
                            <input type="radio" name="type" value="{{ $type->value }}" @checked(old('type', 'return') === $type->value) class="mt-1 accent-secondary">
                            <span>
                                <span class="block text-sm font-bold text-primary">{{ $label }}</span>
                                <span class="block text-xs text-on-surface-variant">{{ $text }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
                @error('type')<p role="alert" class="mt-2 text-sm text-error">{{ $message }}</p>@enderror

                <label class="mt-4 block text-sm font-semibold text-primary">
                    {{ __('Motivo') }}
                    <select name="reason" required class="{{ $field }}">
                        @foreach (ReturnReason::cases() as $reason)
                            <option value="{{ $reason->value }}" @selected(old('reason', 'changed_mind') === $reason->value)>{{ __($reason->getLabel()) }}</option>
                        @endforeach
                    </select>
                </label>
                @error('reason')<p role="alert" class="mt-2 text-sm text-error">{{ $message }}</p>@enderror

                <label class="mt-4 block text-sm font-semibold text-primary">
                    {{ __('Comentario (opcional)') }}
                    <textarea name="comment" rows="3" maxlength="1000" class="{{ $field }}">{{ old('comment') }}</textarea>
                </label>
            </section>

            {{-- 3. Bring it to the shop --}}
            <section aria-labelledby="step-bring">
                <h2 id="step-bring" class="flex items-center gap-3 text-lg font-bold text-primary">
                    <span class="flex size-8 items-center justify-center rounded-full bg-primary text-sm text-white">3</span>
                    {{ __('Tráelo a la tienda') }}
                </h2>
                <p class="mt-3 text-sm text-on-surface-variant">
                    {{ __('La devolución es gratuita en la tienda.') }}
                    @if ($store->address_line) {{ __('Estamos en :address.', ['address' => $store->fullAddress()]) }}@endif
                </p>
            </section>

            @include('store.account.returns.partials.conditions')

            <div>
                <label class="flex items-start gap-3 text-sm text-primary">
                    <input type="checkbox" name="accept" value="1" @checked(old('accept')) class="mt-1 accent-secondary">
                    <span>{{ __('Confirmo que el producto está en buen estado y he leído las condiciones de devolución.') }}</span>
                </label>
                @error('accept')<p role="alert" class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" class="rounded-xl bg-secondary px-6 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Enviar solicitud') }}</button>
                <a href="{{ route('account.returns') }}" class="text-sm font-semibold text-on-surface-variant hover:text-primary">{{ __('Cancelar') }}</a>
            </div>
        </form>
    @endif
@endsection
