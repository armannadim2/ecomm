{{-- Link attributes for a resolved block URL. Expects: $url (?string). External links open in a new tab. --}}
href="{{ $url }}" @if (\App\Domain\Content\Blocks\Block::isExternal($url)) target="_blank" rel="noopener noreferrer" @endif
