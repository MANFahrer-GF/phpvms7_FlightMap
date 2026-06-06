@extends('app')
@section('title', __('flightmap::messages.title'))

@section('css')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <style>
    .fm-card { border:0; border-radius:16px; overflow:hidden; background:#0b0f17; box-shadow:0 10px 40px rgba(0,0,0,.45); }
    .fm-card-header { background:linear-gradient(180deg,#121826,#0b0f17); border-bottom:1px solid rgba(255,255,255,.06); padding:12px 16px; }
    .fm-title { font-weight:800; letter-spacing:.3px; color:#fff; display:flex; align-items:center; gap:8px; font-size:16px; }
    .fm-title i { color:#22d3ee; }
    .fm-toolbar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .fm-pill { border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.04); color:#cbd5e1; border-radius:999px; padding:7px 15px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:7px; transition:.15s; cursor:pointer; }
    .fm-pill:hover { background:rgba(255,255,255,.09); color:#fff; }
    .fm-pill.active { background:linear-gradient(135deg,#22d3ee,#6366f1); color:#fff; border-color:transparent; box-shadow:0 6px 18px rgba(34,211,238,.35); }
    .fm-select { background:rgba(255,255,255,.05); color:#e2e8f0; border:1px solid rgba(255,255,255,.15); border-radius:999px; padding:6px 14px; font-size:13px; outline:none; }
    .fm-select option { background:#111826; color:#e2e8f0; }
    .fm-info { color:#94a3b8; font-size:12.5px; font-weight:600; }
    #map { background:#0b0f17; }
    .fm-badge-wrap { background:transparent !important; border:0 !important; }
    .fm-badge { border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; color:#06281f;
      background:radial-gradient(circle at 32% 28%,#6ee7b7,#10b981); border:2px solid rgba(255,255,255,.9);
      box-shadow:0 0 16px rgba(16,185,129,.65),0 2px 6px rgba(0,0,0,.45); font-size:12px; }
    #map .leaflet-popup-content-wrapper { background:#111826; color:#e2e8f0; border-radius:12px; box-shadow:0 14px 44px rgba(0,0,0,.6); border:1px solid rgba(255,255,255,.08); }
    #map .leaflet-popup-tip { background:#111826; box-shadow:none; }
    #map .leaflet-popup-content { margin:12px 14px; font-size:13px; }
    #map a.leaflet-popup-close-button { color:#64748b; }
    .fm-pop-h { font-weight:700; font-size:14px; margin-bottom:6px; color:#fff; display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
    .fm-pop-h .cnt { background:linear-gradient(135deg,#22d3ee,#6366f1); color:#fff; border-radius:999px; padding:1px 9px; font-size:12px; font-weight:700; }
    /* aircraft list — aligned grid */
    .fm-ac-list { max-height:240px; overflow:auto; display:flex; flex-direction:column; gap:1px; }
    .fm-ac-row { display:grid; grid-template-columns:24px 96px 50px auto; align-items:center; gap:9px; padding:3px 5px; border-radius:7px; }
    .fm-ac-row:hover { background:rgba(255,255,255,.06); }
    .fm-ac-row img { height:16px; width:auto; max-width:24px; object-fit:contain; background:#fff; border-radius:3px; padding:1px; }
    .fm-ac-reg { color:#5eead4; font-weight:700; text-decoration:none; }
    .fm-ac-reg:hover { text-decoration:underline; }
    .fm-ac-type { color:#8aa0b8; font-size:12px; }
    .fm-ac-al { color:#cbd5e1; font-size:12px; text-align:right; }
    /* connections (airport click in route modes) */
    .fm-conn-sec { margin-top:8px; }
    .fm-conn-h { font-size:12px; font-weight:700; margin-bottom:4px; }
    .fm-conn-h.out { color:#34d399; }
    .fm-conn-h.in { color:#f59e0b; }
    .fm-conn-list { max-height:150px; overflow:auto; display:flex; flex-direction:column; gap:1px; }
    .fm-conn { display:grid; grid-template-columns:48px 1fr auto; align-items:center; gap:9px; padding:2px 4px; border-radius:6px; }
    .fm-conn:hover { background:rgba(255,255,255,.06); }
    .fm-conn .i { font-weight:700; }
    .fm-conn .i.out { color:#6ee7b7; }
    .fm-conn .i.in { color:#fcd34d; }
    .fm-conn .nm { color:#94a3b8; font-size:11.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .fm-conn .c { color:#cbd5e1; font-size:11.5px; text-align:right; }
    .fm-empty { color:#64748b; font-size:12px; }
    #map .leaflet-tooltip.fm-tt { background:#111826; color:#e2e8f0; border:1px solid rgba(255,255,255,.12); box-shadow:0 4px 14px rgba(0,0,0,.5); border-radius:7px; font-weight:600; }
    #map .leaflet-tooltip.fm-tt::before { display:none; }
  </style>
@endsection

@section('content')
  <div class="fm-card mb-10">
    <div class="fm-card-header">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div class="fm-title"><i class="ph-fill ph-map-trifold"></i>@lang('flightmap::messages.title')</div>
        <span id="fm-info" class="fm-info"></span>
      </div>
      <div class="fm-toolbar">
        <button id="fm-mode-my" type="button" class="fm-pill active"><i class="ph-fill ph-airplane-tilt"></i>@lang('flightmap::messages.my_flights')</button>
        <button id="fm-mode-all" type="button" class="fm-pill"><i class="ph-fill ph-globe-hemisphere-west"></i>@lang('flightmap::messages.all_flights')</button>
        <button id="fm-mode-aircraft" type="button" class="fm-pill"><i class="ph-fill ph-airplane"></i>@lang('flightmap::messages.aircraft')</button>
        <select id="fm-pilot" class="fm-select" style="display:none"></select>
      </div>
    </div>
    <div id="map" style="width:100%; height:{{ $map_height }}px"></div>
  </div>
@endsection

@section('scripts')
  <script>
    (function () {
      const leaflet = window.L || window.leaflet || L;
      const map = phpvms.map.render_base_map({ leafletOptions: { providers: { 'CartoDB.DarkMatter': {} }, scrollWheelZoom: true } });
      if (map.scrollWheelZoom) { map.scrollWheelZoom.enable(); }

      const layerLines = new leaflet.FeatureGroup().addTo(map);
      const layerNodes = new leaflet.FeatureGroup().addTo(map);
      const info = document.getElementById('fm-info');
      const pilotSel = document.getElementById('fm-pilot');
      const ROUTE_COLOR = '#22d3ee', OUT_COLOR = '#34d399', IN_COLOR = '#f59e0b', DIM_COLOR = '#475569';
      const DAIRCRAFT_BASE = '{{ url('/daircraft') }}';

      // i18n strings (resolved server-side; locale follows the pilot's phpVMS language)
      const T = {
        loading:      @json(__('flightmap::messages.loading')),
        routes:       @json(__('flightmap::messages.routes')),
        airports:     @json(__('flightmap::messages.airports')),
        clickHint:    @json(__('flightmap::messages.click_hint')),
        click:        @json(__('flightmap::messages.click')),
        aircraftWord: @json(__('flightmap::messages.aircraft_word')),
        aircraftUnit: @json(__('flightmap::messages.aircraft_unit')),
        departuresTo: @json(__('flightmap::messages.departures_to')),
        arrivalsFrom: @json(__('flightmap::messages.arrivals_from')),
        none:         @json(__('flightmap::messages.none')),
        allPilots:    @json(__('flightmap::messages.all_pilots')),
      };

      let routeLayers = [], airportMarkers = {}, currentAirports = {}, highlightActive = false;

      function req(url) { return phpvms.request({ url: url }).then(r => r.data); }
      function clearMap() { layerLines.clearLayers(); layerNodes.clearLayers(); routeLayers = []; airportMarkers = {}; currentAirports = {}; highlightActive = false; }
      function fitNodes() { try { const b = layerNodes.getBounds(); if (b.isValid()) { map.fitBounds(b, { padding: [40, 40] }); } } catch (e) {} }
      function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
      function aptName(icao) { return (currentAirports[icao] && currentAirports[icao].name) || ''; }

      // ---- Mode 1 & 2: route lines (click an airport to see its connections) ----
      function drawRoutes(data) {
        clearMap();
        const lines = (data && data.lines) || [];
        const apts = (data && data.airports) || [];
        let maxC = 1; lines.forEach(l => { if (l.count > maxC) maxC = l.count; });
        lines.forEach(l => {
          const weight = 1 + Math.round(5 * Math.sqrt(l.count) / Math.sqrt(maxC));
          const g = new L.Geodesic([], { color: ROUTE_COLOR, weight: weight, opacity: 0.55, wrap: false });
          g.setLatLngs([[l.from[0], l.from[1]], [l.to[0], l.to[1]]]);
          g.bindTooltip(esc(l.dep) + ' → ' + esc(l.arr) + ' · ' + l.count + '×', { sticky: true, className: 'fm-tt' });
          g.addTo(layerLines);
          routeLayers.push({ layer: g, dep: l.dep, arr: l.arr, count: l.count, baseWeight: weight });
        });
        apts.forEach(a => {
          currentAirports[a.icao] = a;
          const m = leaflet.circleMarker([a.lat, a.lon], { radius: 5, color: '#0b0f17', weight: 1, fillColor: ROUTE_COLOR, fillOpacity: 1, bubblingMouseEvents: false });
          m.bindTooltip(esc(a.icao) + ' — ' + esc(a.name) + ' · ' + T.click, { className: 'fm-tt' });
          m.on('click', () => highlightAirport(a.icao));
          m.addTo(layerNodes);
          airportMarkers[a.icao] = m;
        });
        info.textContent = lines.length + ' ' + T.routes + ' · ' + apts.length + ' ' + T.airports + ' · ' + T.clickHint;
        fitNodes();
      }

      function highlightAirport(icao) {
        highlightActive = true;
        const connected = new Set([icao]);
        const dests = [], origins = [];
        routeLayers.forEach(r => {
          if (r.dep === icao) {
            r.layer.setStyle({ color: OUT_COLOR, opacity: 0.95, weight: Math.max(3, r.baseWeight + 1) }); r.layer.bringToFront();
            connected.add(r.arr); dests.push({ icao: r.arr, count: r.count });
          } else if (r.arr === icao) {
            r.layer.setStyle({ color: IN_COLOR, opacity: 0.95, weight: Math.max(3, r.baseWeight + 1) }); r.layer.bringToFront();
            connected.add(r.dep); origins.push({ icao: r.dep, count: r.count });
          } else {
            r.layer.setStyle({ color: DIM_COLOR, opacity: 0.05, weight: 1 });
          }
        });
        Object.keys(airportMarkers).forEach(k => {
          const m = airportMarkers[k];
          if (k === icao) { m.setStyle({ fillColor: '#ffffff', fillOpacity: 1 }); m.setRadius(6); }
          else if (connected.has(k)) { m.setStyle({ fillColor: '#e2e8f0', fillOpacity: 1 }); m.setRadius(4.5); }
          else { m.setStyle({ fillColor: DIM_COLOR, fillOpacity: 0.5 }); m.setRadius(2.5); }
        });
        dests.sort((a, b) => b.count - a.count); origins.sort((a, b) => b.count - a.count);
        const row = (x, dir) => '<div class="fm-conn"><span class="i ' + dir + '">' + esc(x.icao) + '</span><span class="nm">' + esc(aptName(x.icao)) + '</span><span class="c">' + x.count + '×</span></div>';
        const destHtml = dests.length ? dests.map(x => row(x, 'out')).join('') : '<div class="fm-empty">' + T.none + '</div>';
        const origHtml = origins.length ? origins.map(x => row(x, 'in')).join('') : '<div class="fm-empty">' + T.none + '</div>';
        const apt = currentAirports[icao] || { icao: icao };
        const html = '<div class="fm-pop-h">' + esc(apt.icao) + ' <span class="fm-ac-type">' + esc(apt.name || '') + '</span></div>'
          + '<div class="fm-conn-sec"><div class="fm-conn-h out">▸ ' + T.departuresTo + ' (' + dests.length + ')</div><div class="fm-conn-list">' + destHtml + '</div></div>'
          + '<div class="fm-conn-sec"><div class="fm-conn-h in">◂ ' + T.arrivalsFrom + ' (' + origins.length + ')</div><div class="fm-conn-list">' + origHtml + '</div></div>';
        const m = airportMarkers[icao];
        m.unbindPopup(); m.bindPopup(html, { minWidth: 250, maxWidth: 310 }).openPopup();
      }

      function resetHighlight() {
        if (!highlightActive) return;
        highlightActive = false;
        routeLayers.forEach(r => r.layer.setStyle({ color: ROUTE_COLOR, opacity: 0.55, weight: r.baseWeight }));
        Object.keys(airportMarkers).forEach(k => { airportMarkers[k].setStyle({ fillColor: ROUTE_COLOR, fillOpacity: 1 }); airportMarkers[k].setRadius(5); });
      }
      map.on('click', resetHighlight);

      // ---- Mode 3: current aircraft locations (airline + click reg -> aircraft overview) ----
      function drawAircraft(data) {
        clearMap();
        const arr = (data && data.data) || [];
        arr.forEach(a => {
          const size = Math.max(26, Math.min(58, 22 + a.count * 1.6));
          const icon = leaflet.divIcon({
            className: 'fm-badge-wrap',
            html: '<div class="fm-badge" style="width:' + size + 'px;height:' + size + 'px">' + a.count + '</div>',
            iconSize: [size, size], iconAnchor: [size / 2, size / 2]
          });
          const m = leaflet.marker([a.lat, a.lon], { icon: icon });
          const rows = (a.aircraft || []).map(x => {
            const logo = x.airline_logo ? '<img src="' + esc(x.airline_logo) + '" alt="' + esc(x.airline_icao || '') + '">' : '<span></span>';
            const url = DAIRCRAFT_BASE + '/' + encodeURIComponent(x.reg || '');
            return '<div class="fm-ac-row">' + logo + '<a class="fm-ac-reg" href="' + url + '">' + esc(x.reg || '?') + '</a><span class="fm-ac-type">' + esc(x.type || '') + '</span><span class="fm-ac-al">' + esc(x.airline_icao || '') + '</span></div>';
          }).join('');
          const html = '<div class="fm-pop-h">' + esc(a.icao) + ' <span class="fm-ac-type">' + esc(a.name) + '</span> <span class="cnt">' + a.count + '</span></div><div class="fm-ac-list">' + rows + '</div>';
          m.bindPopup(html, { minWidth: 250, maxWidth: 300 });
          m.bindTooltip('<b>' + esc(a.icao) + '</b> · ' + a.count + ' ' + T.aircraftUnit, { className: 'fm-tt' });
          m.addTo(layerNodes);
        });
        info.textContent = arr.length + ' ' + T.airports + ' · ' + arr.reduce((s, a) => s + a.count, 0) + ' ' + T.aircraftWord;
        fitNodes();
      }

      function setActiveButton(mode) { ['my', 'all', 'aircraft'].forEach(k => document.getElementById('fm-mode-' + k).classList.toggle('active', k === mode)); }
      function setMode(mode) {
        setActiveButton(mode);
        pilotSel.style.display = (mode === 'all') ? '' : 'none';
        info.textContent = T.loading;
        if (mode === 'my') { req('/flightmap/my').then(drawRoutes); }
        else if (mode === 'all') { const q = pilotSel.value ? ('?pilot=' + encodeURIComponent(pilotSel.value)) : ''; req('/flightmap/all' + q).then(drawRoutes); }
        else { req('/flightmap/aircraft').then(drawAircraft); }
      }

      req('/flightmap/pilots').then(d => {
        const opts = ['<option value="" selected>' + T.allPilots + '</option>'].concat(((d && d.data) || []).map(p => '<option value="' + p.id + '">' + esc(p.name) + '</option>'));
        pilotSel.innerHTML = opts.join('');
        pilotSel.addEventListener('change', () => setMode('all'));
      });
      document.getElementById('fm-mode-my').addEventListener('click', () => setMode('my'));
      document.getElementById('fm-mode-all').addEventListener('click', () => setMode('all'));
      document.getElementById('fm-mode-aircraft').addEventListener('click', () => setMode('aircraft'));

      setMode('my');
    })();
  </script>
@endsection
