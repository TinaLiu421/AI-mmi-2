{{--
Reusable Chat Button Component

Props:
- href: Link URL
- icon: Emoji icon to display
- text: Button text
- color: Background color (default: #4f46e5)
- hoverColor: Hover background color (default: #3730a3)
- class: Additional CSS classes
--}}

@props([
    'href' => '#',
    'icon' => '🔘',
    'topText' => 'I WANT TO',
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
          width: 120px;
          height: 110px;
          padding: 6px;
          background: linear-gradient(135deg, {{ $color }}dd, {{ $color }}aa);
          backdrop-filter: blur(10px);
          -webkit-backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
          box-shadow: 0 8px 32px rgba(187, 0, 45, 0.2),
                     inset 0 1px 0 rgba(255, 255, 255, 0.1);
          color: white;
          border-radius: 16px;
          text-decoration: none;
          text-align: center;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          font-size: 16px;
          font-weight: 600;
          position: relative;
          overflow: hidden;"
   onmouseover="this.style.transform='translateY(-2px) scale(1.02)';
               this.style.boxShadow='0 12px 40px rgba(187, 0, 45, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2)';
               this.style.background='linear-gradient(135deg, {{ $hoverColor }}, rgba(187, 0, 45, 0.6))';"
   onmouseout="this.style.transform='translateY(0) scale(1)';
              this.style.boxShadow='0 8px 32px rgba(187, 0, 45, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1)';
              this.style.background='linear-gradient(135deg, {{ $color }}dd, {{ $color }}aa)';">

    <!-- Glass shine effect -->
    <div style="position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
                transform: rotate(45deg);
                transition: all 0.6s ease;
                opacity: 0;"
         class="glass-shine"></div>

    <div style="font-size: 24px; margin-bottom: 8px; position: relative; z-index: 2;">{{ $icon }}</div>
    <div style="position: relative; z-index: 2; text-align: center; width: 100%;">
        <div style="font-size: 14px; font-weight: 400; margin-bottom: 4px; opacity: 0.9;">{!! $topText !!}</div>
        <div style="width: 60%; height: 1px; background: rgba(255, 255, 255, 0.3); margin: 0 auto 4px;"></div>
        <div style="font-size: 20px; font-weight: 700; line-height: 1.2;">{!! $bottomText !!}</div>
    </div>
</a>

<style>
:root {
    --color-primary: #bb002d;
    --color-green: #03b3b2;
    --color-red: #d73d32;
    --color-blue: #5091cd;
}

a:hover .glass-shine {
    opacity: 1 !important;
    animation: shine 0.6s ease-in-out;
}

@keyframes shine {
    0% { transform: translateX(-100%) rotate(45deg); }
    100% { transform: translateX(100%) rotate(45deg); }
}

</style>