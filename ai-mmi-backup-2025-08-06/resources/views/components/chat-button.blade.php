{{--
Reusable Chat Button Component

Props:
- href: Link URL
- topText: Text on top (optional)
- bottomText: Text on bottom (optional)
- imgSrc: Image URL (optional)
- color: Background color (default: #4f46e5)
- hoverColor: Hover background color (default: #3730a3)
- class: Additional CSS classes
--}}

@props([
    'href' => '#',
    'bottomText' => 'MIGRATE',
    'imgSrc' => null,
    'color' => 'var(--color-primary, #bb002d)',
    'hoverColor' => 'rgba(187, 0, 45, 0.8)',
    'class' => ''
])

<a href="{{ $href }}"
   class="{{ $class }}"
   style="display: flex;
          flex-direction: column;
          border-radius: 12%;
          border: 1px solid white;
          overflow: hidden;
          width: 120px;
          height: 110px;
          text-align: center;
          transition: transform 0.2s ease, box-shadow 0.2s ease;"
   onmouseover="this.style.transform='translateY(-2px)';"
   onmouseout="this.style.transform='translateY(0)';">

    @if($imgSrc)
        <div style="flex: 1; display:flex; align-items:center; justify-content:center; background: var(--chat-light);">
            <img src="{{ $imgSrc }}" alt="Button Image" style="max-width: 80%; max-height: 80%;">
        </div>
    @else
         <div style="flex: 1; background: var(--chat-light); color: #ffffff; font-size: 32px; font-weight: 950; display:flex; align-items:center; justify-content:center; width: 100%; margin:0; padding:0;">
        {!! $bottomText !!}
    </div>
    @endif
</a>

<style>
:root {
    --chat-dark: #5091cd; /* dark blue for top */
    --chat-light: #bb002d; /* red */
}
</style>
