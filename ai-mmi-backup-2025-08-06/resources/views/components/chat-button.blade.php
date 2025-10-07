{{--
Reusable Chat Button Component

Props:
- href: Link URL
- bottomText: Text to display
- imgSrc: Image URL (optional)
- color: Background color (default: gradient blue)
- hoverColor: Hover background color (optional)
- class: Additional CSS classes
--}}

@props([
    'href' => '#',
    'bottomText' => 'Get Started',
    'imgSrc' => null,
    'color' => 'linear-gradient(135deg, #002065 0%, #0066ff 100%)',
    'hoverColor' => 'linear-gradient(135deg, #001a54 0%, #0052cc 100%)',
    'class' => ''
])

<a href="{{ $href }}"
   class="{{ $class }} chat-button"
   style="display: inline-flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 8px;
          border-radius: 16px;
          border: 2px solid #cbd5e1;
          overflow: hidden;
          min-width: 140px;
          padding: 20px 32px;
          text-align: center;
          background: {{ $color }};
          color: white;
          text-decoration: none;
          font-size: 1.1rem;
          font-weight: 700;
          box-shadow: 0 4px 12px rgba(0, 32, 101, 0.2);
          transition: all 0.3s ease;"
   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0, 32, 101, 0.3)'; this.style.background='{{ $hoverColor }}';"
   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 32, 101, 0.2)'; this.style.background='{{ $color }}';">

    <span>{!! $bottomText !!}</span>
</a>

<style>
/* Responsive for mobile */
@media (max-width: 768px) {
    .chat-button {
        min-width: 120px !important;
        padding: 16px 24px !important;
        font-size: 1rem !important;
    }
}
</style>
