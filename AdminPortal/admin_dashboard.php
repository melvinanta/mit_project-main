<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$sy   = get_str('sy');
$term = get_str('term');
if ($sy !== '' && !valid_sy($sy)) $sy = '';
if ($term !== '' && !in_array($term, TERMS, true)) $term = '';

$init = analytics($conn, $sy, $term);   
unset($init['students_ranked']);

page_top('Admin Dashboard', 'admin_dashboard.php');
?>

        <div class="welcome my-4 d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h2>Analytics</h2>
                <p class="mb-0">School-wide numbers by grade level and section. Updates by itself.</p>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="live" id="live"><i></i><span id="liveText">Live</span></span>
                <button type="button" class="btn-ghost btn-sm-x" id="pauseBtn"><i class="bi bi-pause-fill"></i><span>Pause</span></button>
            </div>
        </div>

        <div class="glass section glass-form mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-lg-3">
                    <label for="fSy">School year</label>
                    <select id="fSy" class="form-select"><option value="">All school years</option></select>
                </div>
                <div class="col-6 col-lg-3">
                    <label for="fTerm">Term</label>
                    <select id="fTerm" class="form-select"><option value="">All terms</option></select>
                </div>
                <div class="col-12 col-lg-3">
                    <label for="fLevel">Grade level</label>
                    <select id="fLevel" class="form-select"><option value="">All grade levels</option></select>
                </div>
                <div class="col-12 col-lg-3 text-lg-end small opacity-75" id="stamp">&nbsp;</div>
            </div>
        </div>

        <div class="row g-3 mb-3" id="kpis">
            <div class="col-6 col-xl-3"><div class="glass stat-card"><i class="bi bi-person-badge"></i><div class="num" data-k="teachers_active">0</div><div class="lbl">Active teachers</div></div></div>
            <div class="col-6 col-xl-3"><div class="glass stat-card"><i class="bi bi-people"></i><div class="num" data-k="students_active">0</div><div class="lbl">Active students</div></div></div>
            <div class="col-6 col-xl-3"><div class="glass stat-card"><i class="bi bi-diagram-3"></i><div class="num" data-k="sections">0</div><div class="lbl">Sections</div></div></div>
            <div class="col-6 col-xl-3"><div class="glass stat-card"><i class="bi bi-file-earmark-check"></i><div class="num" data-k="pending">0</div><div class="lbl">Pending requests</div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <div class="glass section h-100">
                    <h5>Average grade by grade level</h5>
                    <div id="levelBars" class="mt-3"></div>
                    <p class="small opacity-75 mb-0">The thin line marks the passing grade (<?= (int)PASSING_GRADE ?>).</p>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="glass section h-100">
                    <h5>Grade outcomes <span class="small opacity-75" id="outScope"></span></h5>
                    <div class="donut-wrap mt-3" id="donut"></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="glass section h-100">
                    <h5>Sections</h5>
                    <div class="table-responsive">
                        <table class="table table-glass align-middle mb-0">
                            <thead><tr><th>Grade &amp; Section</th><th>Teacher</th><th class="num">Students</th><th class="num">Average</th><th>Pass rate</th><th class="num">Failed</th><th class="num">INC</th></tr></thead>
                            <tbody id="secBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="glass section h-100">
                    <h5>Top achievers</h5>
                    <div class="achiever-list mt-3" id="top"></div>
                    <p class="small opacity-75 mt-3 mb-0"><span id="honorCount">0</span> student(s) on the honor roll.</p>
                </div>
            </div>
        </div>

<?php
ob_start(); ?>
<script>
(function(){
    var INIT = <?= json_encode($init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var PASS = <?= (int)PASSING_GRADE ?>, EVERY = 8000;
    var data = INIT, paused = false, timer = null, inflight = false;

    function $(id){ return document.getElementById(id); }
    function h(tag, props){
        var el = document.createElement(tag), i, k;
        props = props || {};
        for (k in props){ if (k === 'class') el.className = props[k]; else if (k === 'style') el.setAttribute('style', props[k]); else el.setAttribute(k, props[k]); }
        for (i = 2; i < arguments.length; i++){
            var c = arguments[i];
            if (c == null) continue;
            el.appendChild(typeof c === 'object' ? c : document.createTextNode(String(c)));
        }
        return el;
    }
    function clear(el){ while (el.firstChild) el.removeChild(el.firstChild); }
    function n(v){ return Number(v).toLocaleString(); }
    function f2(v){ return v == null ? '—' : Number(v).toFixed(2); }

    function fillSelect(sel, values, current, allLabel){
        var want = JSON.stringify(values) + '|' + current;
        if (sel.getAttribute('data-sig') === want) return;
        sel.setAttribute('data-sig', want);
        clear(sel);
        sel.appendChild(h('option', {value: ''}, allLabel));
        values.forEach(function(v){
            var o = h('option', {value: v}, v);
            if (v === current) o.selected = true;
            sel.appendChild(o);
        });
        sel.value = current;
    }

    function render(d){
        data = d;
        var level = $('fLevel').value;
        fillSelect($('fSy'), d.years, d.sy, 'All school years');
        fillSelect($('fTerm'), d.terms, d.term, 'All terms');
        fillSelect($('fLevel'), d.levels.map(function(l){ return l.level; }), level, 'All grade levels');

        document.querySelectorAll('#kpis [data-k]').forEach(function(el){ el.textContent = n(d.kpi[el.getAttribute('data-k')] || 0); });
        $('honorCount').textContent = n(d.kpi.honors || 0);
        $('stamp').textContent = 'Updated ' + d.generated;

    
        var lb = $('levelBars'); clear(lb);
        if (!d.levels.length) lb.appendChild(h('div', {class: 'empty'}, 'No sections yet.'));
        d.levels.forEach(function(l){
            var w = l.avg == null ? 0 : Math.max(0, Math.min(100, l.avg));
            lb.appendChild(h('div', {class: 'hbar'},
                h('div', {class: 'nm', title: l.level}, l.level, h('small', {}, l.sections + ' section' + (l.sections === 1 ? '' : 's') + ' · ' + l.students + ' student' + (l.students === 1 ? '' : 's'))),
                h('div', {class: 'trk', role: 'img', 'aria-label': l.level + ' average ' + f2(l.avg)},
                    h('div', {class: 'fill', style: 'width:' + w + '%'}),
                    h('div', {class: 'mark', style: 'left:' + PASS + '%'})),
                h('div', {class: 'val'}, f2(l.avg), h('small', {}, l.pass_rate == null ? 'no grades' : l.pass_rate + '% pass'))
            ));
        });

  
        var o = {passed: 0, failed: 0, inc: 0};
        d.levels.forEach(function(l){ if (!level || l.level === level){ o.passed += l.passed; o.failed += l.failed; o.inc += l.inc; } });
        $('outScope').textContent = level ? '· ' + level : '';
        var tot = o.passed + o.failed + o.inc, dn = $('donut'); clear(dn);
        var C = 2 * Math.PI * 54, ns = 'http://www.w3.org/2000/svg';
        var svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('viewBox', '0 0 140 140'); svg.setAttribute('width', '170'); svg.setAttribute('height', '170'); svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Passed ' + o.passed + ', failed ' + o.failed + ', incomplete ' + o.inc);
        function ring(color, frac, offset){
            var c = document.createElementNS(ns, 'circle');
            c.setAttribute('cx', 70); c.setAttribute('cy', 70); c.setAttribute('r', 54); c.setAttribute('fill', 'none');
            c.setAttribute('stroke', color); c.setAttribute('stroke-width', 18);
            c.setAttribute('stroke-dasharray', Math.max(0, frac * C - (frac > 0 && frac < 1 ? 2 : 0)) + ' ' + C);
            c.setAttribute('stroke-dashoffset', -offset * C);
            c.setAttribute('transform', 'rotate(-90 70 70)');
            svg.appendChild(c);
        }
        ring('rgba(127,127,127,.18)', 1, 0);
        var segs = [['#16a34a', o.passed], ['#dc2626', o.failed], ['#d97706', o.inc]], off = 0;
        if (tot) segs.forEach(function(s){ var fr = s[1] / tot; if (fr > 0) ring(s[0], fr, off); off += fr; });
        var t1 = document.createElementNS(ns, 'text'); t1.setAttribute('x', 70); t1.setAttribute('y', 72); t1.setAttribute('text-anchor', 'middle');
        t1.setAttribute('font-size', 22); t1.setAttribute('font-weight', 800); t1.setAttribute('fill', 'currentColor'); t1.textContent = n(tot); svg.appendChild(t1);
        var t2 = document.createElementNS(ns, 'text'); t2.setAttribute('x', 70); t2.setAttribute('y', 90); t2.setAttribute('text-anchor', 'middle');
        t2.setAttribute('font-size', 11); t2.setAttribute('fill', 'currentColor'); t2.setAttribute('opacity', .65); t2.textContent = 'grades'; svg.appendChild(t2);
        dn.appendChild(svg);
        var lg = h('div', {class: 'legend'});
        [['#16a34a', 'Passed', o.passed], ['#dc2626', 'Failed', o.failed], ['#d97706', 'Incomplete (INC)', o.inc]].forEach(function(s){
            lg.appendChild(h('span', {}, h('i', {style: 'background:' + s[0]}), h('strong', {}, n(s[2])), ' ' + s[1] + (tot ? ' · ' + Math.round(100 * s[2] / tot) + '%' : '')));
        });
        dn.appendChild(lg);

     
        var body = $('secBody'); clear(body);
        var rows = d.sections.filter(function(s){ return !level || s.level === level; });
        if (!rows.length) body.appendChild(h('tr', {}, h('td', {colspan: 7, class: 'text-center opacity-75'}, 'No sections to show.')));
        rows.forEach(function(s){
            var pr = s.pass_rate;
            body.appendChild(h('tr', {},
                h('td', {}, h('strong', {}, s.label), h('div', {class: 'small opacity-75'}, 'S.Y. ' + s.school_year)),
                h('td', {}, s.teacher),
                h('td', {class: 'num'}, s.students),
                h('td', {class: 'num'}, h('strong', {}, f2(s.avg))),
                h('td', {}, pr == null ? h('span', {class: 'opacity-75'}, '—') : h('span', {}, h('span', {class: 'minibar'}, h('b', {style: 'width:' + pr + '%'})), pr + '%')),
                h('td', {class: 'num'}, s.failed), h('td', {class: 'num'}, s.inc)));
        });

      
        var tp = $('top'); clear(tp);
        if (!d.top.length) tp.appendChild(h('div', {class: 'empty py-3'}, 'No grades posted for this period.'));
        d.top.forEach(function(s, i){
            tp.appendChild(h('div', {class: 'achiever'},
                h('span', {class: 'rank r' + (i < 3 ? i + 1 : 0)}, i + 1),
                h('div', {class: 'flex-grow-1'}, h('strong', {}, s.name), h('div', {class: 'small opacity-75'}, s.section)),
                h('div', {class: 'text-end'}, h('strong', {}, f2(s.avg)), s.honor ? h('div', {}, h('span', {class: 'pill gold'}, s.honor)) : null)));
        });
    }

    function setLive(state, text){
        var el = $('live'); el.className = 'live' + (state ? ' ' + state : '');
        $('liveText').textContent = text;
    }
    function load(){
        if (inflight) return; inflight = true;
        var q = 'api_stats.php?sy=' + encodeURIComponent($('fSy').value) + '&term=' + encodeURIComponent($('fTerm').value);
        fetch(q, {credentials: 'same-origin', cache: 'no-store'})
            .then(function(r){
                if (r.status === 401){ location.href = '../index.php'; throw new Error('auth'); }
                if (!r.ok) throw new Error('http ' + r.status);
                return r.json();
            })
            .then(function(d){ render(d); if (!paused) setLive('', 'Live'); })
            .catch(function(e){ if (e.message !== 'auth') setLive('off', 'Reconnecting…'); })
            .then(function(){ inflight = false; });
    }
    function schedule(){
        clearInterval(timer);
        timer = setInterval(function(){ if (!paused && !document.hidden) load(); }, EVERY);
    }

    ['fSy', 'fTerm'].forEach(function(id){ $(id).addEventListener('change', load); });
    $('fLevel').addEventListener('change', function(){ render(data); });
    $('pauseBtn').addEventListener('click', function(){
        paused = !paused;
        this.querySelector('i').className = 'bi ' + (paused ? 'bi-play-fill' : 'bi-pause-fill');
        this.querySelector('span').textContent = paused ? 'Resume' : 'Pause';
        setLive(paused ? 'paused' : '', paused ? 'Paused' : 'Live');
        if (!paused) load();
    });
    document.addEventListener('visibilitychange', function(){ if (!document.hidden && !paused) load(); });

    $('fSy').setAttribute('data-sig', ''); render(INIT);
    // keep the server-chosen filters selected on first paint
    $('fSy').value = INIT.sy; $('fTerm').value = INIT.term;
    schedule();
})();
</script>
<?php page_bottom(ob_get_clean()); ?>