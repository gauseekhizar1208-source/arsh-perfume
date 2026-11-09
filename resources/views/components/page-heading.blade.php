@props(['title', 'eyebrow' => 'THE HOUSE OF ARSH', 'description' => null])
<header class="service-heading">
    <nav class="service-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">{{ $title }}</span></nav>
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h1>{{ $title }}</h1>
    @if($description)<p class="service-description">{{ $description }}</p>@endif
</header>
