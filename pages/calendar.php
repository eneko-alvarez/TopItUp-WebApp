<?php
require_once 'includes/functions.php';
// Fetch all user groups and counters for the filter panel
$groups = getUserGroups($pdo, $user_id);
$unassignedCounters = getUnassignedCounters($pdo, $user_id, null); // passing null to get all, we don't need counts here

$groupsData = [];
foreach ($groups as $group) {
    $counters = getGroupCounters($pdo, $group['id'], null);
    $countersData = [];
    foreach ($counters as $c) {
        $countersData[] = [
            'id' => (int)$c['id'],
            'name' => $c['name'],
            'color' => $c['color']
        ];
    }
    $groupsData[] = [
        'id' => (int)$group['id'],
        'name' => $group['name'],
        'color' => $group['color'],
        'counters' => $countersData
    ];
}

$unassignedData = [];
foreach ($unassignedCounters as $c) {
    $unassignedData[] = [
        'id' => (int)$c['id'],
        'name' => $c['name'],
        'color' => $c['color']
    ];
}

// Flat counterMap for JS to lookup individual colors
$allCounters = getUserCounters($pdo, $user_id);
$counterMap = [];
foreach ($allCounters as $c) {
    $counterMap[(int)$c['id']] = ['id' => (int)$c['id'], 'name' => $c['name'], 'color' => $c['color']];
}

$monthNames = [
    1  => 'Enero', 2  => 'Febrero', 3  => 'Marzo',     4  => 'Abril',
    5  => 'Mayo',  6  => 'Junio',   7  => 'Julio',      8  => 'Agosto',
    9  => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$monthNamesEn = [
    1  => 'January', 2  => 'February', 3  => 'March',    4  => 'April',
    5  => 'May',     6  => 'June',     7  => 'July',      8  => 'August',
    9  => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Re-add missing initial date fetch for the JS state
$initYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$initMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

$stmtLogs = $pdo->prepare("
    SELECT cl.date, cl.counter_id
    FROM counter_logs cl
    JOIN counters c ON cl.counter_id = c.id
    WHERE c.user_id = ?
      AND YEAR(cl.date)  = ?
      AND MONTH(cl.date) = ?
    GROUP BY cl.date, cl.counter_id
    ORDER BY cl.date ASC
");
$stmtLogs->execute([$user_id, $initYear, $initMonth]);
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

$initDates = [];
foreach ($logs as $log) {
    $d = $log['date'];
    $cid = (int)$log['counter_id'];
    if (!isset($initDates[$d])) $initDates[$d] = [];
    if (!in_array($cid, $initDates[$d])) $initDates[$d][] = $cid;
}

?>

<div class="calendar-page">

    <!-- Toolbar (Centered) -->
    <div class="calendar-toolbar" style="justify-content: center;">
        <div class="calendar-nav">
            <button class="cal-nav-btn" id="cal-prev" title="<?= t('calendar.prev_month') ?>">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="cal-month-label" id="cal-month-label"></span>
            <button class="cal-nav-btn" id="cal-next" title="<?= t('calendar.next_month') ?>">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Calendar grid -->
    <div class="calendar-grid-wrapper">
        <div class="calendar-weekdays">
            <?php
            $days = currentLang() === 'es'
                ? ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']
                : ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            foreach ($days as $d) echo "<div class=\"cal-weekday\">$d</div>";
            ?>
        </div>
        <div class="calendar-cells" id="calendar-cells"></div>
    </div>

    <!-- Filter Below Calendar -->
    <div class="calendar-filters-container">
        <!-- Reusing matching styles with Dashboard group cards -->
        <h3 class="cal-filters-title" style="color: #ffffff; font-size: 16px; margin: 10px 0 16px 0;">
            <i class="fas fa-filter"></i> <?= t('calendar.filter') ?>
        </h3>
        
        <?php if (empty($groupsData) && empty($unassignedData)): ?>
            <p class="cal-filter-empty"><?= t('calendar.no_counters') ?></p>
        <?php else: ?>
            <div class="cal-filter-grid">
                <?php foreach ($groupsData as $index => $group): ?>
                    <div class="cal-filter-group-card">
                        <div class="cal-filter-group-header" onclick="toggleFilterGroup(<?= $index ?>)">
                            <i class="fas fa-chevron-right cal-filter-group-chevron" id="chevron-group-<?= $index ?>"></i>
                            <span class="cal-filter-dot" style="background:<?= htmlspecialchars($group['color']) ?>"></span>
                            <span class="cal-filter-name"><?= htmlspecialchars($group['name']) ?></span>
                            
                            <!-- Group Master Checkbox -->
                            <div class="cal-filter-checkbox-wrapper" onclick="event.stopPropagation()">
                                <input type="checkbox" 
                                       class="cal-group-checkbox" 
                                       data-group-id="<?= $group['id'] ?>" 
                                       data-group-color="<?= htmlspecialchars($group['color']) ?>"
                                       checked>
                            </div>
                        </div>
                        
                        <div class="cal-filter-group-children" id="children-group-<?= $index ?>" style="display: none;">
                            <?php foreach ($group['counters'] as $c): ?>
                                <label class="cal-filter-child-label">
                                    <span class="cal-filter-dot" style="background:<?= htmlspecialchars($c['color']) ?>"></span>
                                    <span class="cal-filter-name"><?= htmlspecialchars($c['name']) ?></span>
                                    <input type="checkbox" 
                                           class="cal-counter-checkbox cal-child-checkbox" 
                                           data-counter-id="<?= $c['id'] ?>" 
                                           data-parent-group="<?= $group['id'] ?>"
                                           checked>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($unassignedData as $c): ?>
                    <div class="cal-filter-group-card cal-filter-standalone">
                        <label class="cal-filter-group-header" style="cursor: pointer;">
                            <span class="cal-filter-dot" style="background:<?= htmlspecialchars($c['color']) ?>; margin-left: 20px;"></span>
                            <span class="cal-filter-name"><?= htmlspecialchars($c['name']) ?></span>
                            <div class="cal-filter-checkbox-wrapper">
                                <input type="checkbox" 
                                       class="cal-counter-checkbox cal-standalone-checkbox" 
                                       data-counter-id="<?= $c['id'] ?>" 
                                       checked>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
// Make toggleFilterGroup available globally so inline onclick works
function toggleFilterGroup(index) {
    const childrenDiv = document.getElementById('children-group-' + index);
    const chevron = document.getElementById('chevron-group-' + index);
    if (childrenDiv.style.display === 'none' || childrenDiv.style.display === '') {
        childrenDiv.style.display = 'block';
        chevron.style.transform = 'rotate(90deg)';
    } else {
        childrenDiv.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
    }
}

(function() {
    // ── State ──────────────────────────────────────────────────────────────
    let currentYear  = <?= $initYear ?>;
    let currentMonth = <?= $initMonth ?>;
    let datesData    = <?= json_encode($initDates) ?>;   // { "YYYY-MM-DD": [cid, ...] }
    let counterMap   = <?= json_encode($counterMap) ?>;  // { id: {id,name,color} }
    
    // We maintain a set of selected individual counter IDs
    let selectedIds  = new Set(Object.keys(counterMap).map(Number));
    // And a mapping of group status to determine if a group color should override its children
    // If a group is "fully checked", its ID goes here.
    let fullyCheckedGroups = new Set();
    
    // Initialization: Populate the fullyCheckedGroups
    document.querySelectorAll('.cal-group-checkbox').forEach(cb => {
        if(cb.checked) {
            fullyCheckedGroups.add(Number(cb.dataset.groupId));
        }
    });

    const monthNamesEs = <?= json_encode(array_values($monthNames)) ?>;
    const monthNamesEn = <?= json_encode(array_values($monthNamesEn)) ?>;
    const lang = '<?= currentLang() ?>';

    // ── DOM refs ────────────────────────────────────────────────────────────
    const cellsEl      = document.getElementById('calendar-cells');
    const labelEl      = document.getElementById('cal-month-label');
    const prevBtn      = document.getElementById('cal-prev');
    const nextBtn      = document.getElementById('cal-next');

    // ── Helpers ─────────────────────────────────────────────────────────────
    function monthLabel(y, m) {
        const names = lang === 'es' ? monthNamesEs : monthNamesEn;
        return names[m - 1] + ' ' + y;
    }

    function pad2(n) { return n < 10 ? '0' + n : '' + n; }

    // Build an array of {date, isCurrentMonth} for the grid (Mon-start, 6 rows max)
    function buildDays(year, month) {
        const firstDay = new Date(year, month - 1, 1);
        const lastDay  = new Date(year, month, 0);
        const totalDays = lastDay.getDate();

        // 0=Sun → convert to Mon-based (0=Mon…6=Sun)
        let startDow = firstDay.getDay(); // 0=Sun
        startDow = (startDow + 6) % 7;   // 0=Mon

        const days = [];
        // Leading blanks from previous month
        const prevLast = new Date(year, month - 1, 0).getDate();
        for (let i = startDow - 1; i >= 0; i--) {
            const d = prevLast - i;
            const prevMonth = month === 1 ? 12 : month - 1;
            const prevYear  = month === 1 ? year - 1 : year;
            days.push({ date: prevYear + '-' + pad2(prevMonth) + '-' + pad2(d), current: false });
        }
        // Current month days
        for (let d = 1; d <= totalDays; d++) {
            days.push({ date: year + '-' + pad2(month) + '-' + pad2(d), current: true });
        }
        // Trailing blanks to fill grid (multiple of 7)
        let trailing = 1;
        const nextMonth = month === 12 ? 1 : month + 1;
        const nextYear  = month === 12 ? year + 1 : year;
        while (days.length % 7 !== 0) {
            days.push({ date: nextYear + '-' + pad2(nextMonth) + '-' + pad2(trailing), current: false });
            trailing++;
        }
        return days;
    }

    // Build the CSS background for a day cell given its active counter colors
    function buildColorStyle(colors) {
        if (colors.length === 0) return '';
        if (colors.length === 1) {
            return 'background: ' + hexToRgba(colors[0], 0.55) + ';';
        }
        // Multiple colors: vertical stripes
        const pct  = 100 / colors.length;
        const stops = [];
        colors.forEach((col, i) => {
            const from = (pct * i).toFixed(2) + '%';
            const to   = (pct * (i + 1)).toFixed(2) + '%';
            stops.push(hexToRgba(col, 0.6) + ' ' + from);
            stops.push(hexToRgba(col, 0.6) + ' ' + to);
        });
        return 'background: linear-gradient(to right, ' + stops.join(', ') + ');';
    }

    function hexToRgba(hex, alpha) {
        const clean = hex.replace('#', '');
        const r = parseInt(clean.substring(0, 2), 16);
        const g = parseInt(clean.substring(2, 4), 16);
        const b = parseInt(clean.substring(4, 6), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    // ── Render ──────────────────────────────────────────────────────────────
    function render() {
        labelEl.textContent = monthLabel(currentYear, currentMonth);
        const days = buildDays(currentYear, currentMonth);
        const today = new Date().toISOString().slice(0, 10);

        cellsEl.innerHTML = '';
        days.forEach(({ date, current }) => {
            const cell = document.createElement('div');
            cell.className = 'cal-cell' + (current ? '' : ' cal-cell--other') + (date === today ? ' cal-cell--today' : '');

            // Day number
            const num = document.createElement('span');
            num.className = 'cal-day-num';
            num.textContent = parseInt(date.split('-')[2]);
            cell.appendChild(num);

            // Color strip
            if (datesData[date] && current) {
                // Determine which colors to show for this day based on grouping rules
                let activeColors = new Set();
                let activeCounters = datesData[date].filter(cid => selectedIds.has(cid));
                let handledGroups = new Set();
                
                activeCounters.forEach(cid => {
                    // Check if this counter belongs to any fully checked group
                    const childCb = document.querySelector(`.cal-child-checkbox[data-counter-id="${cid}"]`);
                    if (childCb && childCb.dataset.parentGroup) {
                        const groupId = Number(childCb.dataset.parentGroup);
                        if (fullyCheckedGroups.has(groupId)) {
                            // Only add the group color once
                            if (!handledGroups.has(groupId)) {
                                const groupCb = document.querySelector(`.cal-group-checkbox[data-group-id="${groupId}"]`);
                                activeColors.add(groupCb.dataset.groupColor);
                                handledGroups.add(groupId);
                            }
                        } else {
                            // Group is partially disabled, add individual color
                            if (counterMap[cid]) activeColors.add(counterMap[cid].color);
                        }
                    } else {
                        // Unassigned / Standalone counter
                        if (counterMap[cid]) activeColors.add(counterMap[cid].color);
                    }
                });

                const colorsArray = Array.from(activeColors).filter(Boolean);

                if (colorsArray.length > 0) {
                    const strip = document.createElement('div');
                    strip.className = 'cal-color-strip';
                    strip.setAttribute('style', buildColorStyle(colorsArray));
                    cell.appendChild(strip);
                    cell.classList.add('cal-cell--has-records');
                }
            }

            cellsEl.appendChild(cell);
        });
    }

    // ── Fetch month data ────────────────────────────────────────────────────
    function fetchMonth(year, month) {
        // Show loading state
        cellsEl.innerHTML = '<div class="cal-loading"><i class="fas fa-spinner fa-spin"></i></div>';
        fetch('calendar_data.php?year=' + year + '&month=' + month)
            .then(r => r.json())
            .then(data => {
                datesData   = data.dates;
                render();
            })
            .catch(() => {
                cellsEl.innerHTML = '<p class="cal-error">Error loading data.</p>';
            });
    }

    // ── Navigation ──────────────────────────────────────────────────────────
    prevBtn.addEventListener('click', () => {
        if (currentMonth === 1) { currentMonth = 12; currentYear--; }
        else { currentMonth--; }
        fetchMonth(currentYear, currentMonth);
    });

    nextBtn.addEventListener('click', () => {
        if (currentMonth === 12) { currentMonth = 1; currentYear++; }
        else { currentMonth++; }
        fetchMonth(currentYear, currentMonth);
    });

    // ── Filter Interaction ──────────────────────────────────────────────────
    
    function updateStateAndRender() {
        // Collect all checked individual counters
        selectedIds = new Set(
            [...document.querySelectorAll('.cal-counter-checkbox:checked')]
                .map(c => Number(c.dataset.counterId))
        );
        
        // Collect fully checked groups
        fullyCheckedGroups.clear();
        document.querySelectorAll('.cal-group-checkbox').forEach(gcb => {
            const groupId = gcb.dataset.groupId;
            const children = document.querySelectorAll(`.cal-child-checkbox[data-parent-group="${groupId}"]`);
            if (children.length > 0) {
                const allChecked = Array.from(children).every(child => child.checked);
                if (allChecked) {
                    gcb.checked = true;
                    // gcb.indeterminate = false;
                    fullyCheckedGroups.add(Number(groupId));
                } else {
                    const someChecked = Array.from(children).some(child => child.checked);
                    gcb.checked = false;
                    // gcb.indeterminate = someChecked; // Could use visual indeterminate state if desired
                }
            }
        });
        
        render();
    }

    // Master group checkbox click handler
    document.querySelectorAll('.cal-group-checkbox').forEach(cb => {
        cb.addEventListener('change', (e) => {
            const groupId = e.target.dataset.groupId;
            const children = document.querySelectorAll(`.cal-child-checkbox[data-parent-group="${groupId}"]`);
            children.forEach(child => child.checked = e.target.checked);
            updateStateAndRender();
        });
    });

    // Individual counter checkbox click handler (child or standalone)
    document.querySelectorAll('.cal-counter-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            updateStateAndRender();
        });
    });

    // ── Init ────────────────────────────────────────────────────────────────
    render();

})();
</script>
