{{--
    Logo del sistema (public/images/logo-sp.png). Es una imagen a color con
    fondo transparente, no un ícono de un solo trazo — por eso, a diferencia
    del SVG anterior, las clases "fill-current"/"text-*" que le pasan las
    vistas (pensadas para colorear un SVG) no le cambian el color; se dejan
    donde ya estaban para no tocar cada llamado, pero no hacen nada aquí.
--}}
<img src="{{ asset('images/logo-sp.png') }}" alt="{{ config('app.name', 'Sistema de Papeletas') }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
