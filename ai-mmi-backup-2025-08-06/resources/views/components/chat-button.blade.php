{{--
Reusable Chat Button Component

Props:
- href: Link URL
- text: Button text
- color: Background color (default: #4f46e5)
- hoverColor: Hover background color (default: #3730a3)
- class: Additional CSS classes
--}}

@props([
    'href' => '#',
    'topText' => 'I like to',
    'bottomText' => 'MIGRATE',
    'color' => 'var(--color-primary, #bb002d)',
    'hoverColor' => 'rgba(187, 0, 45, 0.8)',
    'class' => ''
])

<a href="{{ $href }}"
   class="{{ $class }}"
   style="display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-color: white;
        width: 120px;
        height: 110px;
        padding: 0;
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;"
    onmouseover="this.style.transform='translateY(-2px)';"
    onmouseout="this.style.transform='translateY(0)';">

    <div style="position: relative; z-index: 2; text-align: center; width: 100%; display: flex; flex-direction: column;">
    <div style="flex: 1; background: var(--chat-dark, #0b3d91); color: #ffffff; padding: 2px 2px; font-size: 18px; font-weight: 600; display:flex; align-items:center; justify-content:center;">{!! $topText !!}</div>
    <div style="flex: 4; background: var(--chat-light, #83c1ff); color: #ffffff; padding: 12px 8px; font-size: 20px; font-weight: 800; line-height: 2.5; display:flex; align-items:center; justify-content:center;">{!! $bottomText !!}</div>
    </div>
</a>

<style>
:root {
    --chat-dark: #5091cd; /* dark blue for top */
    --chat-light: #bb002d; /* light blue for bottom */
}
</style>