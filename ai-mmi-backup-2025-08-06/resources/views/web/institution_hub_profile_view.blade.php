@extends('web.common')

@push('css')
<style>
/* ── Full-width layout: hide chat panel, expand info-area ── */
main.page-body .info-area{width:100%!important;float:none!important;background:#f1f5f9!important;background-image:none!important;min-height:100vh!important}
main.page-body .info-area::before{display:none!important}
main.page-body .page-content{margin-right:0!important;padding:0!important}
body{background:#f1f5f9!important}
main.page-body .chat-area{display:none!important}
.mobile-chat-button{display:none!important}
/* ── Sticky banner needs !important to beat .info-area > div specificity ── */
.spv-sticky-banner{position:fixed!important}

/* ╔══════════════════════════════════════════════════════╗
   ║   AI-mmi School Profile View — Reference Design      ║
   ╚══════════════════════════════════════════════════════╝ */
:root{
  --spv-navy:#1a2f5e;--spv-navy-dark:#0f1e3d;--spv-accent:#1a5ca8;
  --spv-accent-hover:#1447a0;--spv-border:#e2e8f0;--spv-text:#1e293b;
  --spv-muted:#64748b;--spv-light:#f8fafc;--spv-green:#16a34a;
  --spv-radius:10px;--spv-shadow:0 2px 12px rgba(0,0,0,.08);
  --spv-shadow-lg:0 4px 24px rgba(0,0,0,.12);
  --font:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
.spv-page{font-family:var(--font);font-size:16px;background:#f1f5f9;min-height:100vh;padding-bottom:80px}
.spv-page *,.spv-page *::before,.spv-page *::after{box-sizing:border-box}
/* Page title — slim inline badge, not a massive centred block */
.spv-page-title-wrap{background:var(--spv-navy);padding:8px 24px;display:flex;align-items:center;justify-content:center}
.spv-page-title{font-size:.78em;font-weight:700;color:rgba(255,255,255,.75);margin:0;letter-spacing:.12em;text-transform:uppercase}
/* Top info bar */
.spv-top-bar{background:#fff;border-bottom:1px solid var(--spv-border);padding:10px 24px}
.spv-top-bar-inner{max-width:1200px;margin:0 auto;display:flex;align-items:flex-start;flex-wrap:wrap;gap:8px 18px}
.spv-top-logo-wrap{display:flex;align-items:center;gap:8px;flex-shrink:0}
.spv-top-logo{width:40px;height:40px;border-radius:8px;background:var(--spv-light);border:1px solid var(--spv-border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.spv-top-logo img{width:100%;height:100%;object-fit:contain}
.spv-top-logo-ph{font-size:.82em;font-weight:700;color:var(--spv-accent);text-transform:uppercase;letter-spacing:-.02em}
.spv-top-brand{font-size:.75em;font-weight:700;color:var(--spv-accent);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}
.spv-top-divider{width:1px;height:26px;background:var(--spv-border);flex-shrink:0;margin-top:4px}
.spv-top-inst-name{font-size:1.05em;font-weight:700;color:var(--spv-text);white-space:normal;max-width:600px;line-height:1.3;flex:1;min-width:0}
.spv-top-meta{display:flex;align-items:center;flex-wrap:wrap;gap:5px 12px;width:100%;margin-left:0}
.spv-top-meta-item{display:flex;align-items:center;gap:4px;font-size:.8em;color:var(--spv-muted)}
.spv-top-meta-item i{font-size:.92em}
.spv-top-meta-item .spv-flag{font-size:1.05em}
.spv-top-updated{font-size:.75em;color:var(--spv-muted);display:flex;align-items:center;gap:4px;white-space:nowrap;margin-left:auto}
/* Gallery mosaic */
.spv-gallery-mosaic{position:relative;display:grid;grid-template-columns:2fr 1fr;grid-template-rows:200px 200px;gap:4px;max-height:404px;overflow:hidden;background:#0f1e3d}
.spv-gallery-main{grid-column:1;grid-row:1/3;overflow:hidden;cursor:pointer;position:relative}
.spv-gallery-main img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}
.spv-gallery-main:hover img{transform:scale(1.03)}
.spv-gallery-grid{grid-column:2;grid-row:1/3;display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;gap:4px}
.spv-gallery-thumb{overflow:hidden;cursor:pointer;position:relative;background:#1e3060}
.spv-gallery-thumb img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}
.spv-gallery-thumb:hover img{transform:scale(1.06)}
.spv-gallery-empty{cursor:default}
.spv-gallery-placeholder{width:100%;height:100%;background:linear-gradient(135deg,#1a2f5e 0%,#243d72 100%)}
.spv-gallery-nophoto{grid-column:1/3;grid-row:1/3;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f1e3d 0%,#1a2f5e 60%,#243d72 100%);height:404px}
.spv-gallery-nophoto-initials{font-size:4em;font-weight:800;color:rgba(255,255,255,.22);letter-spacing:-.03em;text-transform:uppercase}
.spv-view-photos-btn{position:absolute;bottom:16px;left:16px;background:rgba(255,255,255,.92);border:none;border-radius:6px;padding:7px 16px;font-size:.84em;font-weight:600;color:var(--spv-text);cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(0,0,0,.25);transition:background .2s;z-index:10}
.spv-view-photos-btn:hover{background:#fff}
@media(max-width:640px){.spv-gallery-mosaic{grid-template-columns:1fr;grid-template-rows:200px;max-height:200px}.spv-gallery-main{grid-column:1;grid-row:1}.spv-gallery-grid{display:none}}
/* Tabs */
.spv-tabs-outer{background:#fff;border-bottom:2px solid var(--spv-border);position:sticky;top:0;z-index:200;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.spv-tabs{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;overflow-x:auto;scrollbar-width:none;gap:0}
.spv-tabs::-webkit-scrollbar{display:none}
.spv-tab{background:none;border:none;border-bottom:3px solid transparent;padding:15px 20px;font-size:.93em;font-weight:500;color:var(--spv-muted);cursor:pointer;white-space:nowrap;transition:color .2s,border-color .2s;margin-bottom:-2px;font-family:var(--font)}
.spv-tab:hover{color:var(--spv-accent)}
.spv-tab.active{color:var(--spv-navy);font-weight:700;border-bottom-color:var(--spv-navy)}
/* Tab panels */
.spv-tab-content{max-width:1200px;margin:0 auto;padding:28px 20px}
.spv-tab-panel{display:none}
.spv-tab-panel.active{display:block}
.spv-overview-cols{display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:stretch;margin-bottom:32px}
@media(max-width:900px){.spv-overview-cols{grid-template-columns:1fr}}
/* Blue info card */
.spv-blue-card{background:var(--spv-navy);border-radius:var(--spv-radius);overflow:hidden;box-shadow:var(--spv-shadow-lg)}
.spv-blue-card-header{background:var(--spv-navy-dark);padding:14px 18px;display:flex;align-items:center;gap:9px}
.spv-blue-card-header-logo{width:36px;height:36px;border-radius:6px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.spv-blue-card-header-logo img{width:100%;height:100%;object-fit:contain}
.spv-blue-card-header-logo-ph{font-size:.75em;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase}
.spv-blue-card-header-title{font-size:.88em;font-weight:700;color:rgba(255,255,255,.9);line-height:1.2}
.spv-blue-card-body{padding:6px 0}
.spv-blue-row{display:flex;align-items:flex-start;padding:10px 18px;border-bottom:1px solid rgba(255,255,255,.07);gap:10px}
.spv-blue-row:last-child{border-bottom:none}
.spv-blue-label{font-size:.76em;color:rgba(255,255,255,.55);font-weight:600;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;min-width:120px;flex-shrink:0;padding-top:1px;display:flex;align-items:center;gap:5px}
.spv-blue-label .spv-row-icon{font-size:1em;opacity:.85;flex-shrink:0}
.spv-blue-value{font-size:.88em;color:#fff;font-weight:500;line-height:1.45;flex:1}
.spv-blue-value strong{color:#93c5fd;font-weight:700}
.spv-blue-value .spv-badge-yes{background:rgba(52,211,153,.18);color:#6ee7b7;border-radius:12px;padding:1px 9px;font-size:.85em;font-weight:700;display:inline-flex;align-items:center;gap:3px}
.spv-blue-value .spv-badge-no{background:rgba(255,255,255,.08);color:rgba(255,255,255,.4);border-radius:12px;padding:1px 9px;font-size:.85em;font-weight:600}
/* Map column */
.spv-map-col{border-radius:var(--spv-radius);overflow:hidden;box-shadow:var(--spv-shadow);min-height:400px;background:#e8edf5;position:relative;display:flex;flex-direction:column}
.spv-map-col iframe{display:block;width:100%;height:100%;min-height:420px;border:none;flex:1}
.spv-map-no-address{display:flex;align-items:center;justify-content:center;height:380px;color:var(--spv-muted);font-size:.9em;flex-direction:column;gap:8px}
.spv-map-no-address i{font-size:2em;color:#cbd5e1}
/* About section */
.spv-about-section{background:#fff;border-radius:var(--spv-radius);padding:28px 32px;margin-bottom:20px;box-shadow:var(--spv-shadow)}
.spv-section-title{font-size:1.45em;font-weight:800;color:var(--spv-text);margin:0 0 16px;letter-spacing:-.02em}
.spv-about-text p{color:#374151;line-height:1.75;margin:0 0 14px;font-size:.97em}
.spv-about-text p:last-child{margin-bottom:0}
.spv-about-text strong.ipv2-desc-stat{color:var(--spv-navy)}
.spv-about-show-more{margin-top:12px;background:none;border:none;color:var(--spv-accent);font-size:.87em;font-weight:600;cursor:pointer;padding:0;font-family:var(--font)}
.spv-about-show-more:hover{text-decoration:underline}
/* Why section */
.spv-why-section{background:#fff;border-radius:var(--spv-radius);padding:28px 32px;margin-bottom:20px;box-shadow:var(--spv-shadow)}
.spv-why-title{font-size:1.2em;font-weight:700;color:var(--spv-text);margin:0 0 18px;display:flex;align-items:center;gap:8px}
.spv-why-title::before{content:'\2605';color:#f59e0b;font-size:.9em}
/* Quality cards grid */
.spv-quality-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
.spv-quality-card{background:linear-gradient(135deg,#f8fafc,#eff6ff);border:1px solid #dbeafe;border-radius:9px;padding:14px 16px;display:flex;align-items:flex-start;gap:11px;transition:box-shadow .2s,transform .15s}
.spv-quality-card:hover{box-shadow:0 4px 16px rgba(26,92,168,.12);transform:translateY(-1px)}
.spv-quality-icon{width:34px;height:34px;border-radius:8px;background:var(--spv-accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.95em}
.spv-quality-icon.c1{background:#1a5ca8}.spv-quality-icon.c2{background:#0f766e}.spv-quality-icon.c3{background:#7c3aed}.spv-quality-icon.c4{background:#b45309}.spv-quality-icon.c5{background:#be185d}.spv-quality-icon.c6{background:#0369a1}.spv-quality-icon.c7{background:#15803d}.spv-quality-icon.c8{background:#b91c1c}
.spv-quality-icon i{color:#fff;font-size:1em}
.spv-quality-body strong{display:block;font-size:.87em;font-weight:700;color:var(--spv-navy);margin-bottom:2px}
.spv-quality-body span{font-size:.82em;color:#4b5563;line-height:1.4}
/* Feature strip */
.spv-feature-strip{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;padding-top:14px;border-top:1px solid var(--spv-border)}
.spv-feature-chip{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:.79em;font-weight:600}
.spv-feature-chip.yes{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.spv-feature-chip.no{background:#f8fafc;color:#94a3b8;border:1px solid #e2e8f0;text-decoration:line-through;opacity:.65}
.spv-feature-chip i{font-size:.9em}
/* Profile strength */
.spv-strength-bar-wrap{margin-top:12px;padding:10px 18px;border-top:1px solid rgba(255,255,255,.07)}
.spv-strength-label{font-size:.72em;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;display:flex;justify-content:space-between}
.spv-strength-track{background:rgba(255,255,255,.12);border-radius:4px;height:5px;overflow:hidden}
.spv-strength-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#34d399,#10b981);transition:width .8s ease}
/* Accordion (About School tab) */
.spv-accordion{background:#fff;border-radius:var(--spv-radius);margin-bottom:10px;box-shadow:var(--spv-shadow);border:1px solid var(--spv-border);border-left:3px solid transparent;overflow:hidden;transition:border-color .2s}
.spv-accordion[open]{border-left-color:var(--spv-accent)}
.spv-accordion summary{padding:16px 20px;font-size:.97em;font-weight:600;color:var(--spv-text);cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;transition:background .15s,color .15s;user-select:none}
.spv-accordion summary::-webkit-details-marker{display:none}
.spv-accordion summary::after{content:'▾';font-size:.8em;color:var(--spv-muted);transition:transform .2s}
.spv-accordion[open] summary::after{transform:rotate(-180deg)}
.spv-accordion[open] summary{background:linear-gradient(90deg,#f0f7ff,#fff);color:var(--spv-accent)}
.spv-accordion-body{padding:4px 20px 20px;color:#374151;font-size:.93em;line-height:1.7}
.spv-accordion-body p{margin:0 0 12px}
.spv-accordion-body p:last-child{margin-bottom:0}
.spv-accordion-body ul{list-style:none;padding:0;margin:0}
.spv-accordion-body ul li{display:flex;align-items:flex-start;gap:8px;padding:5px 0;border-bottom:1px solid var(--spv-border)}
.spv-accordion-body ul li:last-child{border-bottom:none}
.spv-accordion-body ul li i{color:var(--spv-green);margin-top:3px;flex-shrink:0}
/* Programs tab */
.spv-programs-header{display:flex;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:20px}
.spv-programs-title{font-size:1.35em;font-weight:800;color:var(--spv-text);margin:0;display:flex;align-items:center;gap:9px}
.spv-programs-title i{color:var(--spv-accent)}
.spv-badge{background:var(--spv-navy);color:#fff;font-size:.6em;font-weight:700;border-radius:20px;padding:3px 9px;vertical-align:middle;letter-spacing:.02em}
.spv-programs-toolbar{display:flex;align-items:center;gap:10px;margin-left:auto;flex-wrap:wrap}
.spv-search-input{border:1px solid var(--spv-border);border-radius:7px;padding:8px 13px;font-size:.86em;font-family:var(--font);color:var(--spv-text);width:220px;outline:none;transition:border-color .2s}
.spv-search-input:focus{border-color:var(--spv-accent)}
.spv-filter-btn,.spv-sort-btn{border:1px solid var(--spv-border);border-radius:7px;padding:8px 14px;font-size:.84em;font-weight:600;font-family:var(--font);color:var(--spv-text);background:#fff;cursor:pointer;display:flex;align-items:center;gap:6px;transition:border-color .2s,box-shadow .2s}
.spv-filter-btn:hover,.spv-sort-btn:hover{border-color:var(--spv-accent);box-shadow:0 2px 8px rgba(26,92,168,.1)}
.spv-level-filters{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:18px}
.spv-level-btn{border:1px solid var(--spv-border);border-radius:20px;padding:5px 14px;font-size:.8em;font-weight:600;font-family:var(--font);color:var(--spv-muted);background:#fff;cursor:pointer;transition:all .2s}
.spv-level-btn:hover{border-color:var(--spv-accent);color:var(--spv-accent)}
.spv-level-btn.active{background:var(--spv-navy);border-color:var(--spv-navy);color:#fff}
/* Program card */
.spv-program-card{background:#fff;border-radius:var(--spv-radius);border:1px solid var(--spv-border);margin-bottom:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);transition:box-shadow .2s,border-color .2s}
.spv-program-card:hover{box-shadow:0 4px 20px rgba(26,47,94,.12);border-color:#c5d2e8}
.spv-prog-main{padding:18px 20px 0}
.spv-prog-inst-name{font-size:.78em;color:var(--spv-muted);font-weight:500;margin-bottom:4px;display:flex;align-items:center}
.spv-prog-name{font-size:1.05em;font-weight:700;color:var(--spv-text);line-height:1.3;margin-bottom:14px}
/* 4-column info grid */
.spv-prog-info-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-top:1px solid var(--spv-border);margin:0 -20px;padding:0 20px}
@media(max-width:640px){.spv-prog-info-grid{grid-template-columns:repeat(2,1fr)}}
.spv-prog-info-col{padding:12px 18px;display:flex;flex-direction:column;gap:6px;border-right:1px solid var(--spv-border)}
.spv-prog-info-col:first-child{padding-left:20px}
.spv-prog-info-col:last-child{border-right:none;padding-right:0}
.spv-prog-info-col+.spv-prog-info-col{padding-left:14px}
.spv-prog-info-label{font-size:.72em;font-weight:700;color:var(--spv-muted);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap}
.spv-prog-info-value{font-size:.92em;font-weight:600;color:var(--spv-text)}
.spv-prog-info-value.spv-tuition{color:var(--spv-green)}
/* Program card action row */
.spv-prog-actions{display:flex;align-items:center;gap:12px;padding:13px 20px;border-top:1px solid var(--spv-border);flex-wrap:wrap}
.spv-prog-details-btn{border:1.5px solid var(--spv-accent);border-radius:7px;padding:8px 18px;font-size:.85em;font-weight:600;font-family:var(--font);color:var(--spv-accent);background:transparent;cursor:pointer;transition:background .2s,color .2s;white-space:nowrap}
.spv-prog-details-btn:hover{background:var(--spv-accent);color:#fff}
.spv-prog-apply-box{display:flex;align-items:stretch;border:1.5px solid var(--spv-text);border-radius:7px;overflow:hidden;text-decoration:none}
.spv-prog-chat,.spv-prog-apply{display:flex;align-items:center;justify-content:center;padding:8px 16px;font-size:.85em;font-weight:600;color:var(--spv-text);text-decoration:none;font-family:var(--font);transition:background .2s;cursor:pointer;white-space:nowrap;background:transparent;border:none}
.spv-prog-chat{gap:6px}
.spv-prog-chat:hover{background:#f1f5f9;color:var(--spv-text)}
.spv-prog-apply{border-left:1.5px solid var(--spv-text)}
.spv-prog-apply:hover{background:var(--spv-text);color:#fff}
/* Program details (collapsible) */
.spv-prog-details{padding:16px 20px 20px;border-top:1px solid var(--spv-border);background:#f9fafb}
.spv-prog-details-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:640px){.spv-prog-details-grid{grid-template-columns:1fr}}
.spv-detail-block h4{font-size:.8em;font-weight:700;color:var(--spv-accent);text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px}
.spv-detail-block p{font-size:.88em;color:#374151;line-height:1.65;margin:0}
.spv-req-table{width:100%;border-collapse:collapse;font-size:.85em}
.spv-req-table td{padding:5px 8px 5px 0;color:#374151;vertical-align:top}
.spv-req-table td:first-child{font-weight:600;color:var(--spv-muted);width:110px;white-space:nowrap}
.spv-prog-overview{font-size:.88em;color:#374151;line-height:1.65;margin:0 0 4px}
.spv-no-results{text-align:center;padding:60px 20px;color:var(--spv-muted)}
.spv-no-results i{font-size:2em;display:block;margin-bottom:10px}
/* Admission / Fees text */
.spv-text-content{background:#fff;border-radius:var(--spv-radius);padding:24px 28px;box-shadow:var(--spv-shadow);color:#374151;font-size:.95em;line-height:1.75}
.spv-text-content h4.inst-section-subhead{font-size:.78em;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--spv-accent);background:linear-gradient(90deg,#dbeafe,#eff6ff 70%,transparent);padding:6px 12px 6px 10px;border-left:3px solid var(--spv-accent);border-radius:0 4px 4px 0;margin:20px 0 8px;display:flex;align-items:center;gap:6px}
.spv-text-content ul.inst-section-list{list-style:none;padding:0;margin:0 0 12px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;overflow:hidden}
.spv-text-content ul.inst-section-list li{padding:7px 14px;border-bottom:1px solid #f1f5f9;display:flex;align-items:flex-start;gap:9px;font-size:.9em}
.spv-text-content ul.inst-section-list li:last-child{border-bottom:none}
.spv-text-content ul.inst-section-list li::before{content:'›';color:var(--spv-accent);font-weight:700;margin-top:0;font-size:1.1em;line-height:1.5;flex-shrink:0}
.spv-text-content li.inst-contact-admissions{color:var(--spv-accent);font-style:italic}
.spv-text-content p{margin:0 0 10px}
/* Key dates */
.inst-dates-list .inst-date-item{display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid #f1f5f9}
.inst-dates-list .inst-date-item:last-child{border-bottom:none}
.inst-date-badge{font-size:.74em;font-weight:700;border-radius:6px;padding:4px 10px;white-space:nowrap;flex-shrink:0;min-width:80px;text-align:center}
.inst-date-badge-red{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.inst-date-badge-green{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0}
.inst-date-badge-blue{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe}
.inst-date-desc{font-size:.88em;color:#374151;font-weight:500}
/* Back link */
.spv-back-bar{background:#fff;border-bottom:1px solid var(--spv-border);padding:10px 24px}
.spv-back-link{display:inline-flex;align-items:center;gap:6px;font-size:.85em;font-weight:600;color:var(--spv-accent);text-decoration:none}
.spv-back-link:hover{text-decoration:underline}
/* Empty state */
.spv-empty-state{text-align:center;padding:80px 24px;color:var(--spv-muted)}
.spv-empty-state i{font-size:3em;display:block;margin-bottom:14px;color:#cbd5e1}
.spv-empty-state p{margin:0 0 8px}
/* Pill lists */
.spv-pill-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
.spv-pill{background:#eff6ff;color:var(--spv-accent);border:1px solid #bfdbfe;border-radius:20px;padding:3px 12px;font-size:.8em;font-weight:600}
.spv-pill-green{background:#f0fdf4!important;color:#15803d!important;border-color:#bbf7d0!important}
.spv-pill-purple{background:#faf5ff!important;color:#7c3aed!important;border-color:#e9d5ff!important}
/* Sticky bottom banner */
.spv-sticky-banner{position:fixed;bottom:0;left:0;right:0;background:var(--spv-navy);color:#fff;padding:14px 28px;font-size:.9em;font-weight:600;display:flex;align-items:center;justify-content:center;gap:12px;z-index:500;box-shadow:0 -4px 20px rgba(0,0,0,.25);text-align:center}
.spv-sticky-banner a{color:#93c5fd;text-decoration:underline;text-underline-offset:2px}
.spv-sticky-banner a:hover{color:#fff}
.spv-banner-chat-btn{background:var(--spv-accent);color:#fff!important;border-radius:6px;padding:6px 16px;text-decoration:none!important;font-weight:700;font-size:.88em;display:inline-flex;align-items:center;gap:6px;transition:background .2s;flex-shrink:0}
.spv-banner-chat-btn:hover{background:var(--spv-accent-hover)!important}
.spv-banner-close{background:none;border:none;color:rgba(255,255,255,.5);font-size:1.2em;cursor:pointer;padding:0 0 0 8px;line-height:1;flex-shrink:0;transition:color .2s}
.spv-banner-close:hover{color:#fff}
/* Lightbox */
.spv-lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;align-items:center;justify-content:center;flex-direction:column}
.spv-lightbox.open{display:flex}
.spv-lb-close{position:absolute;top:16px;right:22px;background:none;border:none;color:#fff;font-size:2.2em;cursor:pointer;z-index:2;line-height:1}
.spv-lb-prev,.spv-lb-next{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.12);border:none;color:#fff;font-size:2em;cursor:pointer;z-index:2;border-radius:6px;padding:8px 14px;line-height:1;transition:background .2s}
.spv-lb-prev:hover,.spv-lb-next:hover{background:rgba(255,255,255,.22)}
.spv-lb-prev{left:16px}
.spv-lb-next{right:16px}
.spv-lb-img{max-width:90vw;max-height:85vh;object-fit:contain;border-radius:8px}
.spv-lb-caption{color:#94a3b8;font-size:.82em;margin-top:12px}
/* Inline apply CTA in admission section */
.spv-apply-cta-inline{background:linear-gradient(90deg,#eff6ff,#f0fdf4);border:1px solid #bfdbfe;border-radius:7px;padding:12px 16px;margin:12px 0;font-size:.88em;color:var(--spv-text);display:flex;align-items:center;gap:8px}
.spv-apply-cta-inline i{color:var(--spv-accent);font-size:1.05em}
.spv-apply-cta-inline a{color:var(--spv-accent);font-weight:700;text-decoration:underline}
/* Responsive */
@media(max-width:768px){
  .spv-quality-grid{grid-template-columns:1fr 1fr}
  .spv-tab-content{padding:18px 14px}
  .spv-about-section,.spv-why-section{padding:20px 18px}
  .spv-map-col iframe{min-height:260px}
  .spv-programs-toolbar{flex-direction:column;align-items:flex-start}
  .spv-search-input{width:100%}
  .spv-prog-apply-box{flex-direction:column}
  .spv-prog-apply{border-left:none;border-top:1.5px solid var(--spv-text)}
  .spv-programs-header{flex-direction:column;align-items:flex-start}
  .spv-programs-toolbar{margin-left:0;width:100%}
}
@media(max-width:480px){
  .spv-top-bar{padding:8px 12px}
  .spv-top-inst-name{font-size:.95em}
  .spv-blue-label{min-width:105px;font-size:.72em}
  .spv-blue-value{font-size:.83em}
  .spv-sticky-banner{font-size:.8em;padding:12px 16px;flex-wrap:wrap}
  .spv-quality-grid{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<?php
$_profile  = $_page_data['profile'] ?? [];
$_courses  = $_page_data['courses'] ?? [];
$profileId = (int)($_profile['id'] ?? 0);

$_rawInstName = $_profile['institute_name'] ?? 'Institution';
$_rawInstName = preg_replace('/\s*\(trading\s+as\s+[^)]+\)/i', '', $_rawInstName);
$_rawInstName = preg_replace('/\s*\bPty\.?\s+Ltd\.?\b/i', '', $_rawInstName);
$_rawInstName = preg_replace('/\s*\bPty\.?\b(?=\s*$)/i', '', $_rawInstName);
$_rawInstName = preg_replace('/\s*,?\s*\bLimited\b/i', '', $_rawInstName);
$_rawInstName = preg_replace('/\s*,?\s*\bInc\.?\b(?=\s*$)/i', '', $_rawInstName);
$_rawInstName = trim($_rawInstName);
if (empty($_rawInstName)) $_rawInstName = $_profile['institute_name'] ?? 'Institution';

$instName  = htmlspecialchars($_rawInstName, ENT_QUOTES);
// Custom display mappings for known long/complex names (keeps top bar concise)
$customDisplayPatterns = [
  '/lan-?grove|lan grove|lan-grove/i' => 'SBTA & SELA - Sydney Business and Travel Academy & the Sydney English Language Academy',
  '/\bSBTA\b.*\bSELA\b/i' => 'SBTA & SELA - Sydney Business and Travel Academy & the Sydney English Language Academy',
];
foreach($customDisplayPatterns as $pat => $disp){ if(preg_match($pat, $_rawInstName)){ $instName = htmlspecialchars($disp, ENT_QUOTES); break; } }
$avatar    = $_profile['avatar'] ?? '';
// NOTE: website_url is NEVER shown to the public
$country   = $_profile['country'] ?? '';
$category  = $_profile['institution_category'] ?? '';
$city      = $_profile['city'] ?? '';
$address   = $_profile['address'] ?? '';
$phone     = $_profile['phone'] ?? '';
$bannerImage       = $_profile['banner_image'] ?? '';
$schoolPhases      = $_profile['school_phases'] ?? '';
$annualFeesRange   = $_profile['annual_fees_range'] ?? '';
$prospectusUrl     = $_profile['prospectus_url'] ?? '';
$studentTeacherRatio = $_profile['student_teacher_ratio'] ?? '';
$academicYear      = $_profile['academic_year'] ?? '';
$missionStatement  = $_profile['mission_statement'] ?? '';
$description       = $_profile['description'] ?? '';
$profileStrength   = (int)($_profile['profile_strength'] ?? 0);

// New structured fields
$costOfLiving       = $_profile['cost_of_living'] ?? '';
$institutionIntakes = $_profile['intakes'] ?? '';
$visaRequirements   = $_profile['visa_requirements'] ?? '';
$registrationNumber = $_profile['registration_number'] ?? '';

$_ipv2_decode_json = function($raw) {
    if (empty($raw)) return [];
    $d = json_decode($raw, true);
    return is_array($d) ? array_values(array_filter($d)) : [];
};
$curriculum            = $_ipv2_decode_json($_profile['curriculum'] ?? '');
$examBoards            = $_ipv2_decode_json($_profile['exam_boards'] ?? '');
$qualificationsAwarded = $_ipv2_decode_json($_profile['qualifications_awarded'] ?? '');
$languageOfInstruction = $_ipv2_decode_json($_profile['language_of_instruction'] ?? '');
$schoolQualities       = $_ipv2_decode_json($_profile['school_qualities'] ?? '');
$examResults           = $_ipv2_decode_json($_profile['exam_results'] ?? '');

// Social links — website key excluded from public display
$socialLinks = [];
$_slRaw = $_profile['social_links'] ?? '';
if (!empty($_slRaw)) {
    $slDecoded = json_decode($_slRaw, true);
    if (is_array($slDecoded)) {
        foreach ($slDecoded as $slk => $slv) {
            if (!empty($slv) && $slk !== 'website') $socialLinks[$slk] = $slv;
        }
    }
}

$hasBoarding     = isset($_profile['has_boarding']) && $_profile['has_boarding'] !== null ? (int)$_profile['has_boarding'] : null;
$hasSchoolBus    = isset($_profile['has_school_bus']) && $_profile['has_school_bus'] !== null ? (int)$_profile['has_school_bus'] : null;
$hasScholarships = isset($_profile['has_scholarships']) && $_profile['has_scholarships'] !== null ? (int)$_profile['has_scholarships'] : null;
$hasChinese      = isset($_profile['has_chinese_language_support']) && $_profile['has_chinese_language_support'] !== null ? (int)$_profile['has_chinese_language_support'] : null;
$hasExtraLangs   = isset($_profile['has_extra_languages']) && $_profile['has_extra_languages'] !== null ? (int)$_profile['has_extra_languages'] : null;

$summary   = $_profile['summary']   ?? '';
$admission = $_profile['admission'] ?? '';
$fees      = $_profile['fees']      ?? '';
$keyDates  = $_profile['key_dates'] ?? '';

// Logo
$logoSrc = !empty($avatar) ? '/upload/member_logo/' . htmlspecialchars(basename($avatar), ENT_QUOTES) : '';
$_ignoreWords = ['of','and','the','a','an','for','in','at','by','to','&'];
$_nameParts = preg_split('/[\s\(\)\-\/]+/', $_rawInstName, -1, PREG_SPLIT_NO_EMPTY);
$_logoInitials = '';
foreach ($_nameParts as $_p) {
    if (!in_array(strtolower($_p), $_ignoreWords) && preg_match('/\pL/u', $_p)) {
        $_logoInitials .= mb_strtoupper(mb_substr($_p, 0, 1, 'UTF-8'), 'UTF-8');
    }
    if (mb_strlen($_logoInitials, 'UTF-8') >= 3) break;
}
if (empty($_logoInitials)) $_logoInitials = mb_strtoupper(mb_substr($_rawInstName, 0, 2, 'UTF-8'), 'UTF-8');

// Gallery
$_pv_gallery = $_ipv2_decode_json($_profile['gallery_json'] ?? '');

// CRICOS / RTO
$_pv_cricos = ''; $_pv_rto = '';
$_pv_src = $summary . ' ' . $description;
if (!empty($_pv_src)) {
    if (preg_match('/\bCRICOS(?:\s+Provider(?:\s+Code)?)?[#:\s]+([0-9][A-Z0-9]{4,9})\b/i', $_pv_src, $_m)) $_pv_cricos = strtoupper($_m[1]);
    if (preg_match('/\bRTO[#:\s]+([0-9]+)/i', $_pv_src, $_m)) $_pv_rto = $_m[1];
}
$_effectiveRegNum = '';
if (!empty($registrationNumber))   $_effectiveRegNum = $registrationNumber;
elseif (!empty($_pv_cricos))       $_effectiveRegNum = 'CRICOS ' . $_pv_cricos;
elseif (!empty($_pv_rto))          $_effectiveRegNum = 'RTO ' . $_pv_rto;

$catLabels = [
    'university'=>'University','vocational'=>'VET / Vocational','highschool'=>'High School',
    'college'=>'College','language_school'=>'Language School','primary_school'=>'Primary School',
    'secondary_school'=>'Secondary School','international_school'=>'International School',
    'tutoring'=>'Tutoring Centre','other'=>'Institution',
];
$catLabel = $catLabels[$category] ?? ucwords(str_replace('_', ' ', $category));

// Updated date
$updatedAt = $_profile['updated_at'] ?? '';
$updatedLabel = ''; $updatedFull = '';
if (!empty($updatedAt)) {
    try { $updatedLabel = (new DateTime($updatedAt))->format('M Y'); $updatedFull = (new DateTime($updatedAt))->format('d/m/Y'); }
    catch(\Throwable $e) {}
}

// Smart extraction: intakes from courses
if (empty($institutionIntakes) && !empty($_courses)) {
    $_intakeMonths = [];
    $_mMap = ['jan'=>'Jan','feb'=>'Feb','mar'=>'Mar','apr'=>'Apr','may'=>'May','jun'=>'Jun',
              'jul'=>'Jul','aug'=>'Aug','sep'=>'Sep','oct'=>'Oct','nov'=>'Nov','dec'=>'Dec',
              'january'=>'Jan','february'=>'Feb','march'=>'Mar','april'=>'Apr','june'=>'Jun',
              'july'=>'Jul','august'=>'Aug','september'=>'Sep','october'=>'Oct','november'=>'Nov','december'=>'Dec'];
    foreach ($_courses as $_ci) {
        $_cEntry = strtolower($_ci['entry'] ?? $_ci['intake'] ?? '');
        foreach ($_mMap as $_mk => $_mv) {
            if (!empty($_cEntry) && strpos($_cEntry, $_mk) !== false && !in_array($_mv, $_intakeMonths)) {
                $_intakeMonths[] = $_mv;
            }
        }
    }
    if (!empty($_intakeMonths)) $institutionIntakes = implode(', ', array_slice($_intakeMonths, 0, 5));
}
// Smart extraction: visa requirements from admission (visa/subclass/OSHC only, not English tests)
if (empty($visaRequirements) && !empty($admission)) {
    $_vLines = [];
    // First pass: strict — only actual visa/OSHC/financial lines
    foreach (preg_split('/\r?\n/', $admission) as $_vl) {
        $_vl = trim($_vl);
        if (preg_match('/\bvisa\b|subclass\s*\d|\boshc\b|overseas\s+student\s+health|financial\s+capacity|genuine\s+temporary/i', $_vl)) {
            $_clean = ltrim($_vl, '-*\'\"&#x2022;&#xB7; ');
            if (strlen($_clean) > 8 && strlen($_clean) < 130) { $_vLines[] = $_clean; if (count($_vLines) >= 2) break; }
        }
    }
    if (!empty($_vLines)) $visaRequirements = implode('; ', $_vLines);
}
// Extract application deadline from key_dates — must contain a date or month/year, not just a heading
$_appDeadline = '';
if (!empty($keyDates)) {
    foreach (preg_split('/\r?\n/', $keyDates) as $_kdl) {
        $_kdl = ltrim(trim($_kdl), '-*&#x2022;&#xB7; ');
        // Must match deadline keyword AND contain a date/month/number — skip pure all-caps headings
        if (preg_match('/deadline|application\s+close|last\s+day|application\s+due/i', $_kdl)
            && preg_match('/\d|january|february|march|april|may|june|july|august|september|october|november|december/i', $_kdl)
            && !preg_match('/^[A-Z\s\-\/]{6,}:?$/', $_kdl)) {
            $_appDeadline = $_kdl; break;
        }
    }
}

// Country flag
function spv_countryFlag(string $c): string {
    $m = ['australia'=>'&#x1F1E6;&#x1F1FA;','canada'=>'&#x1F1E8;&#x1F1E6;','united kingdom'=>'&#x1F1EC;&#x1F1E7;',
          'uk'=>'&#x1F1EC;&#x1F1E7;','usa'=>'&#x1F1FA;&#x1F1F8;','united states'=>'&#x1F1FA;&#x1F1F8;',
          'new zealand'=>'&#x1F1F3;&#x1F1FF;','malaysia'=>'&#x1F1F2;&#x1F1FE;','singapore'=>'&#x1F1F8;&#x1F1EC;',
          'ireland'=>'&#x1F1EE;&#x1F1EA;','germany'=>'&#x1F1E9;&#x1F1EA;','france'=>'&#x1F1EB;&#x1F1F7;',
          'japan'=>'&#x1F1EF;&#x1F1F5;','china'=>'&#x1F1E8;&#x1F1F3;','india'=>'&#x1F1EE;&#x1F1F3;',
          'indonesia'=>'&#x1F1EE;&#x1F1E9;','philippines'=>'&#x1F1F5;&#x1F1ED;','thailand'=>'&#x1F1F9;&#x1F1ED;',
          'hong kong'=>'&#x1F1ED;&#x1F1F0;','uae'=>'&#x1F1E6;&#x1F1EA;','netherlands'=>'&#x1F1F3;&#x1F1F1;',
          'sweden'=>'&#x1F1F8;&#x1F1EA;','switzerland'=>'&#x1F1E8;&#x1F1ED;','italy'=>'&#x1F1EE;&#x1F1F9;',
          'south korea'=>'&#x1F1F0;&#x1F1F7;','vietnam'=>'&#x1F1FB;&#x1F1F3;','taiwan'=>'&#x1F1F9;&#x1F1FC;'];
    return $m[strtolower(trim($c))] ?? '&#x1F3F3;';
}

$showOverview  = true;
$showAbout     = !empty($summary) || !empty($description) || !empty($missionStatement) || !empty($schoolQualities);
$showPrograms  = !empty($_courses);
$showAdmission = !empty($admission);
$showFees      = !empty($fees) || !empty($keyDates);

$_socialIcons = [
    'facebook' =>['fa fa-facebook','Facebook'],'instagram'=>['fa fa-instagram','Instagram'],
    'youtube'  =>['fa fa-youtube','YouTube'],'linkedin'=>['fa fa-linkedin','LinkedIn'],
    'twitter'  =>['fa fa-twitter','Twitter'],'wechat'=>['fa fa-weixin','WeChat'],
    'bilibili' =>['fa fa-play-circle','Bilibili'],
];

function spv_pillList(array $items, string $cls='spv-pill'): string {
    if (empty($items)) return '';
    $out = '<div class="spv-pill-list">';
    foreach ($items as $item) $out .= '<span class="'.htmlspecialchars($cls,ENT_QUOTES).'">'.htmlspecialchars((string)$item,ENT_QUOTES).'</span>';
    return $out.'</div>';
}

if (!function_exists('courseLevel')) {
    function courseLevel(string $name): string {
        $n = strtolower($name);
        if (str_contains($n,'doctor')||str_contains($n,'phd'))          return 'Doctoral';
        if (str_contains($n,'master')||str_contains($n,'graduate dip')) return 'Postgraduate';
        if (str_contains($n,'bachelor'))     return 'Undergraduate';
        if (str_contains($n,'advanced dip')) return 'Advanced Diploma';
        if (str_contains($n,'diploma'))      return 'Diploma';
        if (str_contains($n,'certificate iv')||str_contains($n,'cert iv')) return 'Certificate IV';
        if (str_contains($n,'certificate iii')) return 'Certificate III';
        if (str_contains($n,'certificate'))  return 'Certificate';
        return 'Program';
    }
}

if (!function_exists('spv_textToHtml')) {
    function spv_textToHtml(string $text): string {
        if (empty(trim($text))) return '';
        // Normalise literal \n (from JSON-encoded storage) to real newlines
        if (strpos($text, '\\n') !== false && substr_count($text, "\n") < 3) {
            $text = str_replace(['\\r\\n','\\n'], "\n", $text);
        }
        $lines = explode("\n", $text); $out = ''; $inList = false;
        $_skipSection = false;
        $_aimmiSteps = [
            'Step 1: Chat with AI-mmi to discuss your study goals and get personalised guidance.',
            'Step 2: AI-mmi helps you choose the right school and program based on your profile and budget.',
            'Step 3: Submit your application through AI-mmi — we handle all paperwork and liaise with the institution.',
            'Step 4: AI-mmi tracks your application status and keeps you informed every step of the way.',
            'Step 5: Receive your offer letter and start your student visa process — AI-mmi supports you throughout.',
        ];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { if ($inList) { $out .= '</ul>'; $inList = false; } $_skipSection = false; continue; }
            if (preg_match('/^[A-Z0-9 \/()\-&:]{6,}:?\s*$/', $line) && $line === strtoupper($line)) {
                if ($inList) { $out .= '</ul>'; $inList = false; }
                $heading = rtrim($line, ':');
                if (preg_match('/APPLICATION\s+PROCESS|HOW\s+TO\s+APPLY|STEPS?\s+TO\s+APPLY|ENROLMENT\s+PROCESS/i', $heading)) {
                    $out .= '<h4 class="inst-section-subhead">HOW TO APPLY THROUGH AI-MMI</h4>';
                    $out .= '<ul class="inst-section-list">';
                    foreach ($_aimmiSteps as $_s) $out .= '<li>'.htmlspecialchars($_s, ENT_QUOTES).'</li>';
                    $out .= '</ul>';
                    $out .= '<div class="spv-apply-cta-inline"><i class="fa fa-comments"></i> Ready to apply? <a href="javascript:void(0)" class="do-toapply" data-sector="migration">Chat with AI-mmi now</a> — it\'s free and takes less than 2 minutes.</div>';
                    $_skipSection = true;
                } else {
                    $_skipSection = false;
                    $out .= '<h4 class="inst-section-subhead">'.htmlspecialchars($heading, ENT_QUOTES).'</h4>';
                }
            } elseif (str_starts_with($line,'- ')||str_starts_with($line,'• ')) {
                if ($_skipSection) continue;
                if (!$inList) { $out .= '<ul class="inst-section-list">'; $inList = true; }
                $_li = ltrim($line, '-• ');
                if (preg_match('/see\s+(institution\s+|the\s+)?website|submit.*application.*at\s+\w+\.\w+|apply.*directly\s+to/i', $_li))
                    $out .= '<li class="inst-contact-admissions"><i class="fa fa-comments"></i> Apply through AI-mmi — we handle all paperwork for you</li>';
                else $out .= '<li>'.htmlspecialchars($_li, ENT_QUOTES).'</li>';
            } else {
                if ($_skipSection) continue;
                if ($inList) { $out .= '</ul>'; $inList = false; }
                $out .= '<p>'.htmlspecialchars($line, ENT_QUOTES).'</p>';
            }
        }
        if ($inList) $out .= '</ul>';
        return $out;
    }
}
if (!function_exists('ipv2_textToHtml')) { function ipv2_textToHtml(string $t): string { return spv_textToHtml($t); } }

if (!function_exists('spv_descToHtml')) {
    function spv_descToHtml(string $text): string {
        if (empty(trim($text))) return '';
        $paras = preg_split('/\n{2,}/', trim($text));
        if (count($paras)===1) $paras = array_values(array_filter(array_map('trim',preg_split('/\n/',$text))));
        $out = '';
        foreach ($paras as $para) {
            $para = trim($para); if (empty($para)) continue;
            $safe = htmlspecialchars($para,ENT_QUOTES);
            $safe = preg_replace('/\b(\d[\d,]*\+?)\s*(students?|countries?|campuses?|staff|researchers?|graduates?|programs?|courses?|years?|nationalities|faculties|schools)\b/i','<strong class="ipv2-desc-stat">$1 $2</strong>',$safe);
            $safe = preg_replace('/\b(top\s+\d+%?|No\.\s*\d+|#\s*\d+|QS\s+Five\s+Stars?|QS\s+[\w\s]+?\d+)(?=[\s,\.\!\?]|$)/i','<strong class="ipv2-desc-stat">$1</strong>',$safe);
            $safe = preg_replace('/\b(\d+(?:\.\d+)?%)\b/','<strong class="ipv2-desc-stat">$1</strong>',$safe);
            $out .= '<p>'.$safe.'</p>';
        }
        return $out ?: '<p>'.htmlspecialchars($text,ENT_QUOTES).'</p>';
    }
}
if (!function_exists('ipv2_descriptionToHtml')) { function ipv2_descriptionToHtml(string $t): string { return spv_descToHtml($t); } }

if (!function_exists('spv_keyDatesToHtml')) {
    function spv_keyDatesToHtml(string $text): string {
        if (empty(trim($text))) return '';
        $lines = explode("\n",$text); $out=''; $inList=false;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line==='') { if ($inList) { $out.='</ul>'; $inList=false; } continue; }
            if (preg_match('/^[A-Z0-9 \/()\-&:]{6,}:?\s*$/',$line)&&$line===strtoupper($line)) {
                if ($inList) { $out.='</ul>'; $inList=false; }
                $out.='<h4 class="inst-section-subhead">'.htmlspecialchars(rtrim($line,':'),ENT_QUOTES).'</h4>';
            } elseif (str_starts_with($line,'- ')||str_starts_with($line,'&#x2022; ')) {
                $item = ltrim($line,'-&#x2022;&#xB7; ');
                if (preg_match('/^(\d{1,2}\s+\w+\s+\d{4})\s*[&#x2014;&#x2013;\-:]\s*(.*)/u',$item,$m)) {
                    if (!$inList) { $out.='<ul class="inst-section-list inst-dates-list">'; $inList=true; }
                    try { $badge=(new DateTime($m[1]))->format('j M Y'); } catch(\Throwable $e) { $badge=$m[1]; }
                    $d=$m[2]; $isDeadline=preg_match('/deadline|close|last\s+day|due|acceptance/i',$d);
                    $isOpen=preg_match('/open|begin|start|launch/i',$d);
                    $cls=$isDeadline?'inst-date-badge-red':($isOpen?'inst-date-badge-green':'inst-date-badge-blue');
                    $out.='<li class="inst-date-item"><span class="inst-date-badge '.$cls.'">'.htmlspecialchars($badge,ENT_QUOTES).'</span><span class="inst-date-desc">'.htmlspecialchars($d,ENT_QUOTES).'</span></li>';
                } else {
                    if (!$inList) { $out.='<ul class="inst-section-list inst-dates-list">'; $inList=true; }
                    $out.='<li>'.htmlspecialchars($item,ENT_QUOTES).'</li>';
                }
            } else {
                if ($inList) { $out.='</ul>'; $inList=false; }
                $out.='<p>'.htmlspecialchars($line,ENT_QUOTES).'</p>';
            }
        }
        if ($inList) $out.='</ul>';
        return $out;
    }
}
if (!function_exists('ipv2_keyDatesToHtml')) { function ipv2_keyDatesToHtml(string $t): string { return spv_keyDatesToHtml($t); } }

// Map query
$_mapQuery = trim(implode(', ', array_filter([$address, $city, $country])));
if (empty($_mapQuery)) $_mapQuery = implode(', ', array_filter([$city, $country]));
$_mapQueryEnc = urlencode($_mapQuery);
?>

<div class="spv-page">
<!-- Back link -->
<div class="spv-back-bar">
  <a href="<?php echo $_page_base_url.'/institution_explore'; ?>" class="spv-back-link">
    <i class="fa fa-arrow-left"></i> Back to Colleges
  </a>
</div>

<!-- Page title — slim dark banner, not a huge centred block -->
<div class="spv-page-title-wrap">
  <span class="spv-page-title">School Profile</span>
</div>

<!-- Top info bar — full name + meta on two sub-rows -->
<div class="spv-top-bar">
  <div class="spv-top-bar-inner">
    <div class="spv-top-logo-wrap">
      <div class="spv-top-logo">
        <?php if(!empty($logoSrc)):?>
        <img src="<?php echo $logoSrc;?>" alt="<?php echo $instName;?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <span class="spv-top-logo-ph" style="display:none"><?php echo htmlspecialchars($_logoInitials,ENT_QUOTES);?></span>
        <?php else:?>
        <span class="spv-top-logo-ph"><?php echo htmlspecialchars($_logoInitials,ENT_QUOTES);?></span>
        <?php endif;?>
      </div>
    </div>
    <div class="spv-top-divider"></div>
    <div style="flex:1;min-width:0">
      <div class="spv-top-inst-name"><?php echo $instName;?></div>
      <div class="spv-top-meta">
        <?php if(!empty($city)||!empty($country)):?>
        <span class="spv-top-meta-item">
          <span class="spv-flag"><?php echo spv_countryFlag($country);?></span>
          <?php echo htmlspecialchars(implode(', ',array_filter([$city,$country])),ENT_QUOTES);?>
        </span>
        <?php endif;?>
        <?php if(!empty($address)):?>
        <span class="spv-top-meta-item"><i class="fa fa-map-marker"></i><?php echo htmlspecialchars($address,ENT_QUOTES);?></span>
        <?php endif;?>
        <?php if(!empty($updatedFull)):?>
        <span class="spv-top-updated" style="margin-left:auto"><i class="fa fa-clock-o"></i> Updated: <?php echo htmlspecialchars($updatedFull,ENT_QUOTES);?></span>
        <?php endif;?>
      </div>
    </div>
  </div>
</div>

<!-- Photo gallery mosaic -->
<div class="spv-gallery-mosaic">
  <?php if(!empty($_pv_gallery)):
    // Prefer non-logo images for the main mosaic photo
    $mainIndex = 0;
    for($i=0;$i<count($_pv_gallery);$i++){
      $fn = basename($_pv_gallery[$i]);
      if(!preg_match('/logo|icon|badge|brand|thumb/i',$fn)) { $mainIndex = $i; break; }
    }
  ?>
  <div class="spv-gallery-main" onclick="spvOpenLightbox(<?php echo $mainIndex;?>)">
    <img src="/upload/inst_gallery/<?php echo htmlspecialchars(basename($_pv_gallery[$mainIndex]),ENT_QUOTES);?>" alt="<?php echo $instName;?>" loading="eager">
  </div>
  <div class="spv-gallery-grid">
    <?php $gIndex=0; for($gi=0;$gi<=3;$gi++):
        // pick the next 4 images skipping the mainIndex
        while(isset($_pv_gallery[$gIndex]) && $gIndex==$mainIndex) $gIndex++;
        $gf = isset($_pv_gallery[$gIndex])?basename($_pv_gallery[$gIndex]):''; $gIndex++;
    ?>
    <div class="spv-gallery-thumb<?php echo empty($gf)?' spv-gallery-empty':'';?>" <?php echo !empty($gf) ? 'onclick="spvOpenLightbox('.($gIndex-1).')"' : ''; ?>>
      <?php if(!empty($gf)):?>
      <img src="/upload/inst_gallery/<?php echo htmlspecialchars($gf,ENT_QUOTES);?>" alt="Photo <?php echo $gi+1;?>" loading="lazy">
      <?php else:?><div class="spv-gallery-placeholder"></div><?php endif;?>
    </div>
    <?php endfor;?>
  </div>
  <button class="spv-view-photos-btn" onclick="spvOpenLightbox(<?php echo $mainIndex;?>)">
    <i class="fa fa-th"></i> View Photos
  </button>
  <?php else:?>
  <div class="spv-gallery-nophoto">
    <span class="spv-gallery-nophoto-initials"><?php echo htmlspecialchars($_logoInitials,ENT_QUOTES);?></span>
  </div>
  <?php endif;?>
</div>

<!-- Navigation tabs -->
<div class="spv-tabs-outer" id="spv-tabs-outer">
  <nav class="spv-tabs" id="spv-tabs">
    <button class="spv-tab active" data-tab="spv-tab-overview">Overview</button>
    <?php if($showAbout):?><button class="spv-tab" data-tab="spv-tab-about">About School</button><?php endif;?>
    <?php if($showPrograms):?><button class="spv-tab" data-tab="spv-tab-programs">Programs (<?php echo count($_courses);?>)</button><?php endif;?>
    <?php if($showAdmission):?><button class="spv-tab" data-tab="spv-tab-admission">Admission</button><?php endif;?>
    <?php if($showFees):?><button class="spv-tab" data-tab="spv-tab-fees">Fees &amp; Dates</button><?php endif;?>
  </nav>
</div>

<!-- Tab content -->
<div class="spv-tab-content">

<!-- OVERVIEW TAB -->
<div class="spv-tab-panel active" id="spv-tab-overview">
  <div class="spv-overview-cols">
    <!-- Blue info card -->
    <div class="spv-blue-card">
      <div class="spv-blue-card-header">
        <div class="spv-blue-card-header-logo">
          <?php if(!empty($logoSrc)):?>
          <img src="<?php echo $logoSrc;?>" alt="<?php echo $instName;?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <span class="spv-blue-card-header-logo-ph" style="display:none"><?php echo htmlspecialchars($_logoInitials,ENT_QUOTES);?></span>
          <?php else:?><span class="spv-blue-card-header-logo-ph"><?php echo htmlspecialchars($_logoInitials,ENT_QUOTES);?></span><?php endif;?>
        </div>
        <div class="spv-blue-card-header-title"><?php echo $instName;?></div>
      </div>
      <div class="spv-blue-card-body">
        <?php if(!empty($city)||!empty($country)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-map-marker spv-row-icon"></i> City / Country</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars(implode(', ',array_filter([$city,$country])),ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php if(!empty($address)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-building-o spv-row-icon"></i> Address</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars($address,ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php if(!empty($catLabel)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-graduation-cap spv-row-icon"></i> Education Level</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars($catLabel,ENT_QUOTES);?><?php if(!empty($schoolPhases)):?> &mdash; <?php echo htmlspecialchars($schoolPhases,ENT_QUOTES);?><?php endif;?></span>
        </div>
        <?php endif;?>
        <?php if(!empty($_effectiveRegNum)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-certificate spv-row-icon"></i> Registration No.</span>
          <span class="spv-blue-value"><strong><?php echo htmlspecialchars($_effectiveRegNum,ENT_QUOTES);?></strong></span>
        </div>
        <?php endif;?>
        <?php // School-level tuition removed: tuition is shown per-program only (avoid duplication)
        /* if(!empty($annualFeesRange)): ?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-money spv-row-icon"></i> Tuition Fees</span>
          <span class="spv-blue-value" style="color:#6ee7b7;font-weight:700"><?php echo htmlspecialchars($annualFeesRange,ENT_QUOTES);?></span>
        </div>
        <?php endif; */ ?>
        <?php if(!empty($costOfLiving)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-home spv-row-icon"></i> Cost of Living</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars($costOfLiving,ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php if(!empty($institutionIntakes)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-calendar spv-row-icon"></i> Intakes</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars($institutionIntakes,ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php if(!empty($_appDeadline)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-clock-o spv-row-icon"></i> App. Deadline</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars($_appDeadline,ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php if(!empty($visaRequirements)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-id-card-o spv-row-icon"></i> Visa Req.</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars($visaRequirements,ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php if($hasScholarships===1):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-star spv-row-icon"></i> Scholarships</span>
          <span class="spv-blue-value"><span class="spv-badge-yes"><i class="fa fa-check"></i> Available</span></span>
        </div>
        <?php endif;?>
        <?php if(!empty($studentTeacherRatio)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-users spv-row-icon"></i> Student:Teacher</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars($studentTeacherRatio,ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php if(!empty($languageOfInstruction)):?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-language spv-row-icon"></i> Language</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars(implode(', ',$languageOfInstruction),ENT_QUOTES);?></span>
        </div>
        <?php endif;?>
        <?php // Removed social 'Follow' links from the summary card as requested (keeps UI focused)
        /* if(!empty($socialLinks)): ?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-share-alt spv-row-icon"></i> Follow</span>
          <span class="spv-blue-value" style="display:flex;flex-wrap:wrap;gap:8px">
            <?php foreach($socialLinks as $_slk=>$_slv):if(empty($_slv))continue;$_sli=$_socialIcons[$_slk]??['fa fa-link',ucfirst($_slk)];?>
            <a href="<?php echo htmlspecialchars((string)$_slv,ENT_QUOTES);?>" target="_blank" rel="noopener noreferrer" style="color:#93c5fd;text-decoration:none;font-size:.85em">
              <i class="<?php echo htmlspecialchars($_sli[0],ENT_QUOTES);?>"></i> <?php echo htmlspecialchars($_sli[1],ENT_QUOTES);?>
            </a>
            <?php endforeach;?>
          </span>
        </div>
        <?php endif; */ ?>

        <?php
        // Campuses: derive from explicit field or from program deliveries (show if multiple)
        $campuses = [];
        if(!empty($_profile['campuses'])){
            if(is_string($_profile['campuses'])){
                $tmp = @json_decode($_profile['campuses'], true);
                if(is_array($tmp)) $campuses = $tmp; else $campuses = array_filter(array_map('trim', explode(";", $_profile['campuses'])));
            } elseif(is_array($_profile['campuses'])) $campuses = $_profile['campuses'];
        }
        // fallback: collect unique delivery locations from courses (split by semicolons)
        if(empty($campuses) && !empty($_courses)){
            foreach($_courses as $_c){
                if(!empty($_c['delivery'])){
                    // A single delivery field may list multiple campuses separated by semicolons
                    foreach(array_map('trim', explode(';', $_c['delivery'])) as $_dl){
                        if(!empty($_dl)) $campuses[] = $_dl;
                    }
                }
            }
        }
        // Normalize: strip trailing bracketed qualifiers, deduplicate
        $campuses = array_values(array_filter(array_unique(array_map(function($v){
            $v = trim(preg_replace('/\s+\([^)]+\)$/','',$v));
            $v = trim(preg_replace('/^(Sydney)\s*$/','',$v)); // remove bare "Sydney" (use city row instead)
            return $v;
        },$campuses))));
        if(!empty($campuses)){
        ?>
        <div class="spv-blue-row">
          <span class="spv-blue-label"><i class="fa fa-map-marker spv-row-icon"></i> Campuses</span>
          <span class="spv-blue-value"><?php echo htmlspecialchars(implode(', ', $campuses), ENT_QUOTES);?></span>
        </div>
        <?php }
        ?>
        <?php
        // Profile strength bar
        $ps = (int)($_profile['profile_strength'] ?? 0);
        if($ps > 0):
        ?>
        <div class="spv-strength-bar-wrap">
          <div class="spv-strength-label"><span>Profile Completeness</span><span><?php echo $ps;?>%</span></div>
          <div class="spv-strength-track"><div class="spv-strength-fill" style="width:<?php echo $ps;?>%"></div></div>
        </div>
        <?php endif;?>
      </div>
    </div>
    <!-- Embedded Google Map -->
    <div class="spv-map-col">
      <?php if(!empty($_mapQuery)):?>
      <iframe src="https://maps.google.com/maps?q=<?php echo $_mapQueryEnc;?>&output=embed&z=15"
        loading="lazy" allowfullscreen style="border:none;width:100%;flex:1;min-height:420px;display:block"
        title="Campus location for <?php echo $instName;?>"></iframe>
      <?php else:?>
      <div class="spv-map-no-address"><i class="fa fa-map-o"></i><span>Address not available</span></div>
      <?php endif;?>
    </div>
  </div>

  <!-- About snippet -->
  <?php
  $_aboutSrc = !empty($description)?$description:(!empty($summary)?$summary:'');
  $_aboutParas = [];
  if(!empty($_aboutSrc)){
    $_aboutParas=preg_split('/\n{2,}/',trim($_aboutSrc));
    if(count($_aboutParas)===1) $_aboutParas=array_values(array_filter(array_map('trim',preg_split('/\n/',$_aboutSrc))));
    $_aboutParas=array_values(array_filter($_aboutParas,function($p){return!empty(trim($p));}));
  }
  ?>
  <?php if(!empty($_aboutParas)):?>
  <div class="spv-about-section">
    <h2 class="spv-section-title">About <?php echo $instName;?></h2>
    <div class="spv-about-text">
      <?php foreach(array_slice($_aboutParas,0,2) as $_ap):?>
      <p><?php $s=htmlspecialchars(trim($_ap),ENT_QUOTES);$s=preg_replace('/\b(\d[\d,]*\+?)\s*(students?|countries?|campuses?|staff|researchers?|graduates?|programs?|courses?|years?|nationalities|faculties|schools)\b/i','<strong class="ipv2-desc-stat">$1 $2</strong>',$s);$s=preg_replace('/\b(\d+(?:\.\d+)?%)\b/','<strong class="ipv2-desc-stat">$1</strong>',$s);echo $s;?></p>
      <?php endforeach;?>
      <?php if(count($_aboutParas)>2):?>
      <div id="spv-about-more" style="display:none">
        <?php foreach(array_slice($_aboutParas,2) as $_ap):?>
        <p><?php $s=htmlspecialchars(trim($_ap),ENT_QUOTES);$s=preg_replace('/\b(\d[\d,]*\+?)\s*(students?|countries?|campuses?|staff|researchers?|graduates?|programs?|courses?|years?|nationalities|faculties|schools)\b/i','<strong class="ipv2-desc-stat">$1 $2</strong>',$s);$s=preg_replace('/\b(\d+(?:\.\d+)?%)\b/','<strong class="ipv2-desc-stat">$1</strong>',$s);echo $s;?></p>
        <?php endforeach;?>
      </div>
      <button class="spv-about-show-more" onclick="var m=document.getElementById('spv-about-more');var o=m.style.display!=='none';m.style.display=o?'none':'block';this.textContent=o?'Read more \u25be':'Show less \u25b4'">Read more &#9662;</button>
      <?php endif;?>
    </div>
  </div>
  <?php endif;?>

  <!-- Why section -->
  <?php
  $_qColorClasses = ['c1','c2','c3','c4','c5','c6','c7','c8'];
  $_qIcons = ['fa-star','fa-trophy','fa-globe','fa-book','fa-users','fa-lightbulb-o','fa-check-circle','fa-flag'];
  ?>
  <?php if(!empty($schoolQualities)):?>
  <div class="spv-why-section">
    <h3 class="spv-why-title">Why <?php echo $instName;?>?</h3>
    <div class="spv-quality-grid">
      <?php foreach($schoolQualities as $_qi=>$_q):$_qt=(string)$_q;$_qp=preg_split('/:\s+/',$_qt,2);$_cc=$_qColorClasses[$_qi%count($_qColorClasses)];$_ic=$_qIcons[$_qi%count($_qIcons)];?>
      <div class="spv-quality-card">
        <div class="spv-quality-icon <?php echo $_cc;?>"><i class="fa <?php echo $_ic;?>"></i></div>
        <div class="spv-quality-body">
          <?php if(count($_qp)===2):?><strong><?php echo htmlspecialchars($_qp[0],ENT_QUOTES);?></strong><span><?php echo htmlspecialchars($_qp[1],ENT_QUOTES);?></span><?php else:?><strong><?php echo htmlspecialchars($_qt,ENT_QUOTES);?></strong><?php endif;?>
        </div>
      </div>
      <?php endforeach;?>
    </div>
    <?php
    // Feature strip — boarding, scholarships, bus, extra languages, Chinese support
    // Feature strip — boarding, scholarships, bus, extra languages, Chinese support
    $_fHtml = '';
    if($hasBoarding===1) $_fHtml.='<span class="spv-feature-chip yes"><i class="fa fa-bed"></i> Boarding</span>';
    else $_fHtml.='<span class="spv-feature-chip no"><i class="fa fa-bed"></i> Boarding</span>';
    if($hasSchoolBus===1) $_fHtml.='<span class="spv-feature-chip yes"><i class="fa fa-bus"></i> School Bus</span>';
    if($hasScholarships===1) $_fHtml.='<span class="spv-feature-chip yes"><i class="fa fa-star"></i> Scholarships</span>';
    if($hasChinese===1) $_fHtml.='<span class="spv-feature-chip yes"><i class="fa fa-language"></i> Chinese Support</span>';
    if($hasExtraLangs===1) $_fHtml.='<span class="spv-feature-chip yes"><i class="fa fa-comments"></i> Extra Languages</span>';
    if(!empty($_fHtml)):?>
    <div class="spv-feature-strip"><?php echo $_fHtml;?></div>
    <?php endif;?>
  </div>
  <?php endif;?>

  <!-- CTA -->
  <div style="text-align:center;margin:28px 0 8px">
    <a href="javascript:void(0);" class="do-toapply"
       style="background:var(--spv-navy);color:#fff;border-radius:8px;padding:12px 28px;font-size:.95em;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px"
       data-sector="migration"
       data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat',ENT_QUOTES);?>"
       data-preset-msg="<?php echo htmlspecialchars('I want to learn more about '.$_rawInstName.'. What programs do they offer, what are the entry requirements, and how do I apply?',ENT_QUOTES);?>">
      <i class="fa fa-comments"></i> Ask AI-mmi About This School
    </a>
  </div>
</div>

<!-- ABOUT SCHOOL TAB -->
<?php if($showAbout):?>
<div class="spv-tab-panel" id="spv-tab-about">
  <?php if(!empty($description)||!empty($summary)):?>
  <details class="spv-accordion" open>
    <summary>About <?php echo $instName;?></summary>
    <div class="spv-accordion-body"><?php echo spv_descToHtml(!empty($description)?$description:$summary);?></div>
  </details>
  <?php endif;?>
  <?php // Mission Statement intentionally hidden (removed per UI/content rules)
  ?>
  <?php if(!empty($schoolQualities)):?>
  <details class="spv-accordion">
    <summary><i class="fa fa-star" style="color:#f59e0b;margin-right:7px"></i>Why <?php echo $instName;?>?</summary>
    <div class="spv-accordion-body">
      <ul>
        <?php foreach($schoolQualities as $_q):?><li><i class="fa fa-check" style="color:#059669"></i><?php echo htmlspecialchars((string)$_q,ENT_QUOTES);?></li><?php endforeach;?>
      </ul>
    </div>
  </details>
  <?php endif;?>
  <?php if(!empty($examResults)):?>
  <details class="spv-accordion">
    <summary><i class="fa fa-trophy" style="color:#f59e0b;margin-right:7px"></i>Exam Results &amp; Achievements</summary>
    <div class="spv-accordion-body">
      <ul>
        <?php foreach($examResults as $_er):?><li><i class="fa fa-trophy" style="color:#f59e0b"></i><?php echo htmlspecialchars((string)$_er,ENT_QUOTES);?></li><?php endforeach;?>
      </ul>
    </div>
  </details>
  <?php endif;?>
  <?php if(!empty($curriculum)||!empty($examBoards)||!empty($qualificationsAwarded)):?>
  <details class="spv-accordion">
    <summary><i class="fa fa-book" style="color:#1a5ca8;margin-right:7px"></i>Curriculum &amp; Qualifications</summary>
    <div class="spv-accordion-body">
      <?php if(!empty($curriculum)):?><p style="font-size:.78em;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin:0 0 6px">Curriculum</p><?php echo spv_pillList($curriculum);?><?php endif;?>
      <?php if(!empty($examBoards)):?><p style="font-size:.78em;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin:12px 0 6px">Exam Boards</p><?php echo spv_pillList($examBoards,'spv-pill spv-pill-green');?><?php endif;?>
      <?php if(!empty($qualificationsAwarded)):?><p style="font-size:.78em;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin:12px 0 6px">Qualifications Awarded</p><?php echo spv_pillList($qualificationsAwarded,'spv-pill spv-pill-purple');?><?php endif;?>
    </div>
  </details>
  <?php endif;?>
  <?php if($hasBoarding||$hasSchoolBus||$hasScholarships===1||$hasChinese||$hasExtraLangs):?>
  <details class="spv-accordion">
    <summary><i class="fa fa-check-circle" style="color:#059669;margin-right:7px"></i>School Features</summary>
    <div class="spv-accordion-body">
      <ul>
        <?php if($hasBoarding):?><li><i class="fa fa-check" style="color:var(--spv-green)"></i>Boarding available</li><?php endif;?>
        <?php if($hasSchoolBus):?><li><i class="fa fa-check" style="color:var(--spv-green)"></i>School bus service</li><?php endif;?>
        <?php if($hasScholarships===1):?><li><i class="fa fa-check" style="color:var(--spv-green)"></i>Scholarships available</li><?php endif;?>
        <?php if($hasChinese):?><li><i class="fa fa-check" style="color:var(--spv-green)"></i>Chinese language support</li><?php endif;?>
        <?php if($hasExtraLangs):?><li><i class="fa fa-check" style="color:var(--spv-green)"></i>Extra language programs</li><?php endif;?>
      </ul>
    </div>
  </details>
  <?php endif;?>
</div>
<?php endif;?>

<!-- PROGRAMS TAB -->
<?php if($showPrograms):
$_levels=[];
foreach($_courses as $_c){if(!empty($_c['name'])){$lv=courseLevel($_c['name']);if(!in_array($lv,$_levels))$_levels[]=$lv;}}
?>
<div class="spv-tab-panel" id="spv-tab-programs">
  <div class="spv-programs-header">
    <h2 class="spv-programs-title"><i class="fa fa-file-text-o"></i> Programs <span class="spv-badge"><?php echo count($_courses);?></span></h2>
    <div class="spv-programs-toolbar">
      <input type="text" id="spv-search" class="spv-search-input" placeholder="Search programs&#x2026;" aria-label="Search programs">
      <button class="spv-filter-btn" type="button"><i class="fa fa-sliders"></i> Filter <i class="fa fa-caret-down"></i></button>
      <button class="spv-sort-btn" type="button"><i class="fa fa-sort-amount-asc"></i> Sort <i class="fa fa-caret-down"></i></button>
    </div>
  </div>
  <?php if(count($_levels)>1):?>
  <div class="spv-level-filters">
    <button class="spv-level-btn active" data-level="" type="button">All (<?php echo count($_courses);?>)</button>
    <?php foreach($_levels as $_lv):?><button class="spv-level-btn" data-level="<?php echo htmlspecialchars($_lv,ENT_QUOTES);?>" type="button"><?php echo htmlspecialchars($_lv,ENT_QUOTES);?></button><?php endforeach;?>
  </div>
  <?php endif;?>
  <div id="spv-program-list">
    <?php foreach($_courses as $_course):
      if(empty($_course['name']))continue;
      $cName    =htmlspecialchars($_course['name']??'',ENT_QUOTES);
      $cCode    =htmlspecialchars($_course['code']??$_course['cricos_code']??'',ENT_QUOTES);
      $cEntry   =htmlspecialchars($_course['entry']??$_course['intake']??'',ENT_QUOTES);
      $cDeadline=htmlspecialchars($_course['deadline']??'',ENT_QUOTES);
      $cTuition =htmlspecialchars($_course['fee_tuition']??'',ENT_QUOTES);
      $cAppFee  =htmlspecialchars($_course['fee_application']??'',ENT_QUOTES);
      $cOshc    =htmlspecialchars($_course['fee_oshc']??'',ENT_QUOTES);
      $cLiving  =htmlspecialchars($_course['fee_living']??'',ENT_QUOTES);
      $cIelts   =htmlspecialchars($_course['req_ielts']??'',ENT_QUOTES);
      $cPte     =htmlspecialchars($_course['req_pte']??'',ENT_QUOTES);
      $cToefl   =htmlspecialchars($_course['req_toefl']??'',ENT_QUOTES);
      $cCambridge=htmlspecialchars($_course['req_cambridge']??'',ENT_QUOTES);
      $cDuolingo=htmlspecialchars($_course['req_duolingo']??'',ENT_QUOTES);
      $cAcademic=htmlspecialchars($_course['req_academic']??'',ENT_QUOTES);
      $cDelivery=htmlspecialchars($_course['delivery']??'',ENT_QUOTES);
      $cDuration=htmlspecialchars($_course['duration']??'',ENT_QUOTES);
      $cOverview=$_course['overview']??$_course['description']??'';
      $cScholarship=htmlspecialchars($_course['scholarships']??'',ENT_QUOTES);
      $cLevel=courseLevel($_course['name']??'');
      $searchData=strtolower(strip_tags(($_course['name']??'').' '.($cCode).' '.($cDelivery).' '.$cLevel));
      $hasDetails=!empty($cOverview)||!empty($cAcademic)||!empty($cIelts)||!empty($cTuition)||!empty($cDelivery);
      // If profile-level key dates exist, prefer school-level dates and hide program-level deadline to avoid duplication
      $showProgramDeadline = empty($keyDates) && !empty($_course['deadline']);
      $courseId = isset($_course['id']) ? (int)$_course['id'] : '';
      $cCardId='spv-card-'.substr(md5($_course['name'].($cCode)),0,8);
    ?>
    <div class="spv-program-card" data-search="<?php echo htmlspecialchars($searchData,ENT_QUOTES);?>" data-level="<?php echo htmlspecialchars($cLevel,ENT_QUOTES);?>">
      <div class="spv-prog-main">
        <div class="spv-prog-inst-name">
          <?php if(!empty($logoSrc)):?><img src="<?php echo $logoSrc;?>" alt="" style="width:16px;height:16px;object-fit:contain;border-radius:3px;margin-right:5px;vertical-align:middle" onerror="this.style.display='none'"><?php endif;?>
          <?php echo $instName;?>
        </div>
        <div class="spv-prog-name"><?php echo $cName;?><?php if(!empty($cCode)):?> <span style="font-size:.78em;font-weight:500;color:var(--spv-muted)">(<?php echo $cCode;?>)</span><?php endif;?></div>
      </div>
        <div class="spv-prog-info-grid">
        <div class="spv-prog-info-col"><span class="spv-prog-info-label">Earliest Intake</span><span class="spv-prog-info-value"><?php echo!empty($cEntry)?$cEntry:'&#x2014;';?></span></div>
        <div class="spv-prog-info-col"><span class="spv-prog-info-label">Duration</span><span class="spv-prog-info-value"><?php echo!empty($cDuration)?$cDuration:'&#x2014;';?></span></div>
        <div class="spv-prog-info-col"><span class="spv-prog-info-label">Tuition</span><span class="spv-prog-info-value<?php echo!empty($cTuition)?' spv-tuition':'';?>"><?php echo!empty($cTuition)?$cTuition:'&#x2014;';?></span></div>
        <div class="spv-prog-info-col"><span class="spv-prog-info-label">Application Fee</span><span class="spv-prog-info-value"><?php echo!empty($cAppFee)?$cAppFee:'&#x2014;';?></span></div>
      </div>
      <div class="spv-prog-actions">
        <?php if($hasDetails):?>
        <button class="spv-prog-details-btn" type="button" onclick="spvToggleDetails('<?php echo $cCardId;?>',this)" aria-expanded="false" aria-controls="<?php echo $cCardId;?>">Program Details</button>
        <?php else:?><button class="spv-prog-details-btn" type="button" style="opacity:.45;cursor:default" disabled>Program Details</button><?php endif;?>
        <div class="spv-prog-apply-box">
          <a href="javascript:void(0);" class="spv-prog-chat do-toapply"
             data-sector="migration"
             data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat',ENT_QUOTES);?>"
             data-preset-msg="<?php echo htmlspecialchars('Tell me about the '.($_course['name']??'').' program at '.$_rawInstName.'. What are the entry requirements, fees, and how do I apply?',ENT_QUOTES);?>">
            <i class="fa fa-comments"></i> Chat with AI-mmi
          </a>
          <?php
            if(!empty($courseId)){
              $applyUrl = $_page_base_url.'/apply?institution_id='.$profileId.'&course_id='.$courseId;
            } else {
              $applyUrl = $_page_base_url.'/apply?institution='.urlencode($_rawInstName).'&course='.urlencode($_course['name']??'').'&prefill_course='.urlencode($_course['name']??'');
            }
          ?>
          <a href="<?php echo htmlspecialchars($applyUrl,ENT_QUOTES);?>" class="spv-prog-apply">Apply</a>
        </div>
      </div>
      <?php if($hasDetails):?>
      <div class="spv-prog-details" id="<?php echo $cCardId;?>" style="display:none">
        <div class="spv-prog-details-grid">
          <?php if(!empty($cOverview)):?>
          <div class="spv-detail-block" style="grid-column:1/-1"><h4>Overview</h4><p><?php echo nl2br(htmlspecialchars($cOverview,ENT_QUOTES));?></p></div>
          <?php endif;?>
          <?php if(!empty($cAcademic)||!empty($cIelts)||!empty($cPte)||!empty($cToefl)||!empty($cCambridge)||!empty($cDuolingo)):?>
          <div class="spv-detail-block"><h4>Entry Requirements</h4>
            <table class="spv-req-table">
              <?php if(!empty($cAcademic)):?><tr><td>Academic</td><td><?php echo $cAcademic;?></td></tr><?php endif;?>
              <?php if(!empty($cIelts)):?><tr><td>IELTS</td><td><?php echo $cIelts;?></td></tr><?php endif;?>
              <?php if(!empty($cPte)):?><tr><td>PTE</td><td><?php echo $cPte;?></td></tr><?php endif;?>
              <?php if(!empty($cToefl)):?><tr><td>TOEFL iBT</td><td><?php echo $cToefl;?></td></tr><?php endif;?>
              <?php if(!empty($cCambridge)):?><tr><td>Cambridge</td><td><?php echo $cCambridge;?></td></tr><?php endif;?>
              <?php if(!empty($cDuolingo)):?><tr><td>Duolingo</td><td><?php echo $cDuolingo;?></td></tr><?php endif;?>
            </table>
          </div>
          <?php endif;?>
          <?php if(!empty($cTuition)||!empty($cAppFee)||!empty($cOshc)||!empty($cLiving)):?>
          <div class="spv-detail-block"><h4>Fees</h4>
            <table class="spv-req-table">
              <?php if(!empty($cTuition)):?><tr><td>Tuition</td><td style="color:var(--spv-green);font-weight:600"><?php echo $cTuition;?></td></tr><?php endif;?>
              <?php if(!empty($cAppFee)):?><tr><td>Application</td><td><?php echo $cAppFee;?></td></tr><?php endif;?>
              <?php if(!empty($cOshc)):?><tr><td>OSHC</td><td><?php echo $cOshc;?></td></tr><?php endif;?>
              <?php if(!empty($cLiving)):?><tr><td>Living Costs</td><td><?php echo $cLiving;?></td></tr><?php endif;?>
            </table>
          </div>
          <?php endif;?>
          <?php if(!empty($cDelivery)||!empty($cDuration)):?>
          <div class="spv-detail-block"><h4>Delivery</h4>
            <table class="spv-req-table">
              <?php if(!empty($cDelivery)):?><tr><td>Location</td><td><?php echo $cDelivery;?></td></tr><?php endif;?>
              <?php if(!empty($cDuration)):?><tr><td>Duration</td><td><?php echo $cDuration;?></td></tr><?php endif;?>
            </table>
          </div>
          <?php endif;?>
          <?php if(!empty($cScholarship)):?>
          <div class="spv-detail-block" style="grid-column:1/-1"><h4>Scholarships</h4><p><?php echo nl2br($cScholarship);?></p></div>
          <?php endif;?>
        </div>
      </div>
      <?php endif;?>
    </div>
    <?php endforeach;?>
  </div>
  <div id="spv-no-results" class="spv-no-results" style="display:none"><i class="fa fa-search"></i><p>No programs match your search.</p></div>
</div>
<?php endif;?>

<!-- ADMISSION TAB -->
<?php if($showAdmission):?>
<div class="spv-tab-panel" id="spv-tab-admission">
  <div class="spv-text-content">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:12px;border-bottom:2px solid #e9d5ff">
      <span style="background:#f3e8ff;color:#7c3aed;border-radius:8px;width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;font-size:1.05em;flex-shrink:0"><i class="fa fa-file-text-o"></i></span>
      <span style="font-size:1em;font-weight:700;color:var(--spv-navy)">Admission Requirements</span>
    </div>
    <?php echo spv_textToHtml($admission);?>
    <?php if(strpos($admission, 'HOW TO APPLY THROUGH AI-MMI') === false && strpos($admission, 'APPLICATION PROCESS') === false):?>
    <h4 class="inst-section-subhead" style="margin-top:20px">HOW TO APPLY THROUGH AI-MMI</h4>
    <ul class="inst-section-list">
      <li>Step 1: Chat with AI-mmi to discuss your study goals and get personalised guidance.</li>
      <li>Step 2: AI-mmi helps you choose the right school and program based on your profile and budget.</li>
      <li>Step 3: Submit your application through AI-mmi &mdash; we handle all paperwork and liaise with the institution.</li>
      <li>Step 4: AI-mmi tracks your application status and keeps you informed every step of the way.</li>
      <li>Step 5: Receive your offer letter and start your student visa process &mdash; AI-mmi supports you throughout.</li>
    </ul>
    <div class="spv-apply-cta-inline"><i class="fa fa-comments"></i> Ready to apply? <a href="javascript:void(0)" class="do-toapply" data-sector="migration">Chat with AI-mmi now</a> &mdash; it's free and takes less than 2 minutes.</div>
    <?php endif;?>
  </div>
</div>
<?php endif;?>

<!-- FEES & DATES TAB -->
<?php if($showFees):?>
<div class="spv-tab-panel" id="spv-tab-fees">
  <?php if(!empty($fees)):?>
  <div class="spv-text-content" style="margin-bottom:14px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:12px;border-bottom:2px solid #dbeafe">
      <span style="background:#dbeafe;color:#1a5ca8;border-radius:8px;width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;font-size:1.05em;flex-shrink:0"><i class="fa fa-money"></i></span>
      <span style="font-size:1em;font-weight:700;color:var(--spv-navy)">Tuition &amp; Fee Structure</span>
    </div>
    <?php
      // Flag only truly non-authoritative fee entries for manual verification
      if(preg_match('/\b(example vocational|not specified|tbc|to be confirmed)\b/i', $fees)){
        echo '<p style="color:#b45309;font-weight:700;margin-bottom:8px">Note: Fee details appear to be example/reference text — please verify against the institution website.</p>';
      }
    ?>
    <?php echo spv_textToHtml($fees);?>
  </div>
  <?php endif;?>
  <?php if(!empty($keyDates)):?>
  <div class="spv-text-content">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:12px;border-bottom:2px solid #dcfce7">
      <span style="background:#dcfce7;color:#16a34a;border-radius:8px;width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;font-size:1.05em;flex-shrink:0"><i class="fa fa-calendar"></i></span>
      <span style="font-size:1em;font-weight:700;color:var(--spv-navy)">Key Dates &amp; Deadlines</span>
    </div>
    <?php echo spv_keyDatesToHtml($keyDates);?>
  </div>
  <?php endif;?>
</div>
<?php endif;?>

<?php if(!$showOverview&&!$showAbout&&!$showPrograms&&!$showAdmission&&!$showFees):?>
<div class="spv-empty-state">
  <i class="fa fa-building-o"></i>
  <p style="font-size:1.1em;font-weight:700;color:var(--spv-text)">Profile Coming Soon</p>
  <p>This institution&#39;s profile is being set up. Check back soon.</p>
</div>
<?php endif;?>
</div>

<!-- Lightbox -->
<div id="spv-lightbox" class="spv-lightbox">
  <button class="spv-lb-close" onclick="spvCloseLightbox()" aria-label="Close">&times;</button>
  <button class="spv-lb-prev" id="spv-lb-prev" onclick="spvLbNav(-1)">&#x2039;</button>
  <img id="spv-lb-img" class="spv-lb-img" src="" alt="Gallery photo">
  <button class="spv-lb-next" id="spv-lb-next" onclick="spvLbNav(1)">&#x203A;</button>
  <div class="spv-lb-caption" id="spv-lb-caption"></div>
</div>
</div>

<!-- Sticky bottom banner -->
<div class="spv-sticky-banner" id="spv-sticky-banner">
  <span>Not meeting English or admission requirements? Don&#39;t worry &#8212; <a href="<?php echo htmlspecialchars($_page_base_url.'/agent_chat',ENT_QUOTES);?>">talk to AI-mmi</a> and we will help you!</span>
  <a href="javascript:void(0);" class="spv-banner-chat-btn do-toapply" data-sector="migration"
     data-action-url="<?php echo htmlspecialchars($_page_base_url.'/agent_chat',ENT_QUOTES);?>"
     data-preset-msg="<?php echo htmlspecialchars("I don't meet the requirements for ".$_rawInstName.". Can you help me find a pathway?",ENT_QUOTES);?>">
    <i class="fa fa-comments"></i> Chat with AI-mmi
  </a>
  <button class="spv-banner-close" onclick="document.getElementById('spv-sticky-banner').style.display='none'" aria-label="Dismiss">&times;</button>
</div>

@endsection

@push('scripts')
<script>
(function(){
'use strict';
// Tab switching
var _tabs=document.querySelectorAll('.spv-tab');
var _panels=document.querySelectorAll('.spv-tab-panel');
_tabs.forEach(function(btn){
  btn.addEventListener('click',function(){
    _tabs.forEach(function(b){b.classList.remove('active');b.setAttribute('aria-selected','false');});
    _panels.forEach(function(p){p.classList.remove('active');});
    btn.classList.add('active');btn.setAttribute('aria-selected','true');
    var t=document.getElementById(btn.getAttribute('data-tab'));
    if(t)t.classList.add('active');
    btn.scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'});
  });
});
// Program details toggle
window.spvToggleDetails=function(id,btn){
  var el=document.getElementById(id);if(!el)return;
  var open=el.style.display!=='none';
  el.style.display=open?'none':'';
  if(btn){btn.textContent=open?'Program Details':'Close Details';btn.setAttribute('aria-expanded',open?'false':'true');}
};
// Search & level filter
var _si=document.getElementById('spv-search');
var _al='';
function _filter(){
  var q=_si?_si.value.toLowerCase().trim():'';
  var cards=document.querySelectorAll('#spv-program-list .spv-program-card');
  var vis=0;
  cards.forEach(function(c){
    var mq=!q||(c.getAttribute('data-search')||'').indexOf(q)!==-1;
    var ml=!_al||(c.getAttribute('data-level')||'')===_al;
    var show=mq&&ml;c.style.display=show?'':'none';if(show)vis++;
  });
  var nr=document.getElementById('spv-no-results');if(nr)nr.style.display=vis===0?'':'none';
}
if(_si)_si.addEventListener('input',_filter);
document.querySelectorAll('.spv-level-btn').forEach(function(b){
  b.addEventListener('click',function(){
    document.querySelectorAll('.spv-level-btn').forEach(function(x){x.classList.remove('active');});
    this.classList.add('active');_al=this.getAttribute('data-level')||'';_filter();
  });
});
// Gallery lightbox
var _g=<?php echo json_encode(array_values(array_map(function($f){return basename($f);},$_pv_gallery)));?>;
var _li=0,_lbEl=document.getElementById('spv-lightbox'),_lbImg=document.getElementById('spv-lb-img'),_lbCap=document.getElementById('spv-lb-caption'),_lbPrev=document.getElementById('spv-lb-prev'),_lbNext=document.getElementById('spv-lb-next');
window.spvOpenLightbox=function(idx){if(!_g.length)return;_li=idx;_lbShow();if(_lbEl)_lbEl.classList.add('open');document.body.style.overflow='hidden';};
window.spvCloseLightbox=function(){if(_lbEl)_lbEl.classList.remove('open');document.body.style.overflow='';};
window.spvLbNav=function(dir){_li=(_li+dir+_g.length)%_g.length;_lbShow();};
function _lbShow(){if(_lbImg)_lbImg.src='/upload/inst_gallery/'+_g[_li];if(_lbCap)_lbCap.textContent=(_li+1)+' / '+_g.length;if(_lbPrev)_lbPrev.style.display=_g.length>1?'':'none';if(_lbNext)_lbNext.style.display=_g.length>1?'':'none';}
if(_lbEl)_lbEl.addEventListener('click',function(e){if(e.target===_lbEl)window.spvCloseLightbox();});
document.addEventListener('keydown',function(e){if(!_lbEl||!_lbEl.classList.contains('open'))return;if(e.key==='Escape')window.spvCloseLightbox();if(e.key==='ArrowLeft')window.spvLbNav(-1);if(e.key==='ArrowRight')window.spvLbNav(1);});
})();
</script>
@endpush
