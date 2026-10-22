<x-layouts.portal>
    <x-layouts.portal-header :title="$title"></x-layouts.portal-header>
    <x-layouts.portal-section-hero :title="$title"></x-layouts.portal-section-hero>
    <x-layouts.portal-section-stats></x-layouts.portal-section-stats>
    <x-layouts.portal-section-about></x-layouts.portal-section-about>
    <x-layouts.portal-section-courses></x-layouts.portal-section-courses>
    <x-layouts.portal-section-news :cursos="$cursos"></x-layouts.portal-section-news>
    <x-layouts.portal-section-contact></x-layouts.portal-section-contact>
    <x-layouts.portal-footer></x-layouts.portal-footer>
</x-layouts.portal>
