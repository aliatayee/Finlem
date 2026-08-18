@props(['class' => ''])

<img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" {{ $attributes->class(['object-contain dark:hidden']) }}>
<img src="{{ asset('logo-white.png') }}" alt="{{ config('app.name') }}" {{ $attributes->class(['object-contain hidden dark:block']) }}>
