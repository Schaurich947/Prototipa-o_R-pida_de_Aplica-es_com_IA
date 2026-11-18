<?php /* Shared PDF styles (Dompdf supports CSS 2.1 plus a few CSS3 properties: tables lay out the grid). */ ?>
<style>
    @page { margin: 18px 20px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 7.5pt; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 2px 4px; }
    .box { border: 1px solid #000; margin-bottom: 4px; }
    .box td { border: 1px solid #999; }
    .section { background: #e6e6e6; font-weight: bold; font-size: 7pt; text-transform: uppercase; }
    .label { display: block; font-size: 6pt; color: #333; }
    .value { display: block; font-size: 7.5pt; font-weight: bold; }
    .center { text-align: center; }
    .right { text-align: right; }
    .mono { font-family: "DejaVu Sans Mono", monospace; }
    .test-banner { border: 1.5px solid #c00; color: #c00; text-align: center; font-weight: bold; padding: 3px; margin-bottom: 4px; }
    .watermark { position: fixed; top: 38%; left: 0; width: 100%; text-align: center; font-size: 46pt; font-weight: bold; color: #c00; opacity: 0.12; transform: rotate(-30deg); }
</style>
