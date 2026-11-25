{{--
    Renders one page-builder block inside its wrapper: "Diseño" options (background, spacing, visibility)
    and, in the visual editor preview, the markers the editor script uses (outline, toolbar, drop zones).
    Expects: $block (Block), $headingLevel (1|2)
--}}
@php $editing = \App\Domain\Content\Blocks\PageEditing::active(); @endphp
<div @class(['pb-block', $block->wrapperClasses(), 'pb-is-hidden' => $editing && $block->get('is_hidden')])
     @if ($editing)
         data-pb-index="{{ $block->index }}"
         data-pb-type="{{ $block->type }}"
         data-pb-label="{{ \App\Domain\Content\Blocks\BlockTypes::label($block->type) }}"
     @endif>
    @include('store.blocks.'.$block->type, ['block' => $block, 'headingLevel' => $headingLevel])
</div>
