<?php
error_reporting(0);
ini_set("display_errors","0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UniReg — Smart Course Registration</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/* ──────────────────────────────────────────────────────────
   DESIGN TOKENS
────────────────────────────────────────────────────────── */
:root {
  --bg-void:      #070d1a;
  --bg-deep:      #0c1628;
  --bg-card:      #101e35;
  --bg-card-2:    #142240;
  --bg-hover:     #1a2d50;
  --border:       rgba(255,255,255,0.07);
  --border-bright:rgba(255,255,255,0.15);

  --gold:         #f0b429;
  --gold-dim:     #c9900a;
  --gold-glow:    rgba(240,180,41,0.18);

  --teal:         #38bdf8;
  --teal-dim:     #0ea5e9;

  --green:        #34d399;
  --orange:       #fb923c;
  --red:          #f87171;

  --text-bright:  #f0f4ff;
  --text-mid:     #8b9ab5;
  --text-dim:     #4a5a78;

  --font-display: 'Playfair Display', Georgia, serif;
  --font-body:    'DM Sans', sans-serif;
  --font-mono:    'DM Mono', monospace;

  --radius-sm:    6px;
  --radius-md:    12px;
  --radius-lg:    18px;
  --radius-xl:    24px;

  --shadow-card:  0 4px 24px rgba(0,0,0,0.4), 0 1px 3px rgba(0,0,0,0.6);
  --shadow-glow:  0 0 40px rgba(240,180,41,0.12);
  --transition:   0.22s cubic-bezier(0.4,0,0.2,1);
}

/* ──────────────────────────────────────────────────────────
   BASE
────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

html, body {
  min-height: 100vh;
  background: var(--bg-void);
  color: var(--text-bright);
  font-family: var(--font-body);
  font-size: 15px;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

/* Subtle grid overlay */
body::before {
  content: '';
  position: fixed; inset: 0;
  background-image:
    linear-gradient(rgba(56,189,248,0.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(56,189,248,0.025) 1px, transparent 1px);
  background-size: 44px 44px;
  pointer-events: none;
  z-index: 0;
}

/* Ambient glow blobs */
body::after {
  content: '';
  position: fixed;
  width: 600px; height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(240,180,41,0.06) 0%, transparent 70%);
  top: -150px; right: -150px;
  pointer-events: none; z-index: 0;
}

a { color: var(--teal); text-decoration: none; }
a:hover { color: var(--gold); }

/* ──────────────────────────────────────────────────────────
   SCROLLBAR
────────────────────────────────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--bg-deep); }
::-webkit-scrollbar-thumb { background: var(--bg-hover); border-radius: 3px; }

/* ──────────────────────────────────────────────────────────
   LAYOUT
────────────────────────────────────────────────────────── */
.app-wrapper { position: relative; z-index: 1; min-height: 100vh; }

/* TOPBAR */
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 32px;
  background: rgba(12,22,40,0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 100;
}
.topbar-brand {
  display: flex; align-items: center; gap: 10px;
}
.brand-icon {
  width: 36px; height: 36px; border-radius: 8px;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dim) 100%);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; color: var(--bg-void);
  box-shadow: 0 0 20px rgba(240,180,41,0.35);
}
.brand-name {
  font-family: var(--font-display);
  font-size: 1.25rem; font-weight: 700;
  color: var(--text-bright);
  letter-spacing: -0.01em;
}
.brand-name span { color: var(--gold); }

.topbar-nav { display: flex; gap: 4px; align-items: center; }
.nav-tab {
  display: flex; align-items: center; gap: 7px;
  padding: 8px 16px; border-radius: var(--radius-sm);
  cursor: pointer; transition: var(--transition);
  color: var(--text-mid); font-weight: 500; font-size: 0.875rem;
  border: none; background: none;
  position: relative;
}
.nav-tab:hover { color: var(--text-bright); background: var(--bg-hover); }
.nav-tab.active { color: var(--gold); background: var(--gold-glow); }
.nav-tab.active::after {
  content: ''; position: absolute; bottom: -1px; left: 50%; transform: translateX(-50%);
  width: 30px; height: 2px; background: var(--gold); border-radius: 2px;
}

.topbar-right { display: flex; align-items: center; gap: 12px; }
.student-selector {
  background: var(--bg-card);
  border: 1px solid var(--border-bright);
  color: var(--text-bright);
  padding: 8px 36px 8px 14px;
  border-radius: var(--radius-sm);
  font-family: var(--font-body); font-size: 0.875rem;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b9ab5' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  cursor: pointer;
  transition: var(--transition);
  min-width: 200px;
}
.student-selector:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px var(--gold-glow); }

/* MAIN CONTENT */
.main-content { padding: 32px; max-width: 1400px; margin: 0 auto; }

/* PANELS */
.panel { display: none; animation: fadeSlide 0.35s ease forwards; }
.panel.active { display: block; }

@keyframes fadeSlide {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* SECTION HEADER */
.section-header {
  margin-bottom: 28px;
}
.section-header h2 {
  font-family: var(--font-display);
  font-size: 2rem; font-weight: 700;
  color: var(--text-bright);
  margin: 0 0 4px;
  letter-spacing: -0.02em;
}
.section-header p {
  color: var(--text-mid); margin: 0; font-size: 0.9rem;
}

/* CARDS */
.card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }

.course-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 22px;
  transition: var(--transition);
  position: relative; overflow: hidden;
}
.course-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, transparent, var(--teal-dim), transparent);
  opacity: 0; transition: var(--transition);
}
.course-card:hover {
  border-color: var(--border-bright);
  background: var(--bg-card-2);
  transform: translateY(-2px);
  box-shadow: var(--shadow-card);
}
.course-card:hover::before { opacity: 1; }

.course-card-name {
  font-family: var(--font-display);
  font-size: 1.05rem; font-weight: 600;
  color: var(--text-bright);
  margin-bottom: 14px;
  line-height: 1.35;
}
.course-meta {
  display: flex; flex-direction: column; gap: 7px;
  margin-bottom: 16px;
}
.meta-row {
  display: flex; align-items: center; gap: 8px;
  font-size: 0.82rem; color: var(--text-mid);
}
.meta-row i { color: var(--teal); font-size: 0.9rem; width: 14px; }

.seat-bar {
  height: 5px; border-radius: 3px;
  background: rgba(255,255,255,0.08);
  margin-bottom: 14px; overflow: hidden;
}
.seat-bar-fill {
  height: 100%; border-radius: 3px;
  transition: width 0.6s ease;
}
.seat-bar-fill.full    { background: var(--red); }
.seat-bar-fill.partial { background: var(--green); }
.seat-bar-fill.low     { background: var(--orange); }

.seat-info {
  display: flex; justify-content: space-between; align-items: center;
  font-size: 0.78rem; color: var(--text-dim); margin-bottom: 16px;
}

.badge-status {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04em;
  text-transform: uppercase;
}
.badge-enrolled  { background: rgba(52,211,153,0.15); color: var(--green); border: 1px solid rgba(52,211,153,0.3); }
.badge-waitlisted{ background: rgba(251,146,60,0.15);  color: var(--orange);border: 1px solid rgba(251,146,60,0.3); }
.badge-available { background: rgba(56,189,248,0.10);  color: var(--teal);  border: 1px solid rgba(56,189,248,0.2); }
.badge-full      { background: rgba(248,113,113,0.12); color: var(--red);   border: 1px solid rgba(248,113,113,0.25);}

/* BUTTONS */
.btn-primary-custom {
  width: 100%;
  padding: 10px 16px;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dim) 100%);
  color: var(--bg-void); font-weight: 600; font-size: 0.85rem;
  border: none; border-radius: var(--radius-sm);
  cursor: pointer; transition: var(--transition);
  display: flex; align-items: center; justify-content: center; gap: 7px;
  box-shadow: 0 2px 12px rgba(240,180,41,0.25);
}
.btn-primary-custom:hover {
  filter: brightness(1.1);
  box-shadow: 0 4px 20px rgba(240,180,41,0.4);
  transform: translateY(-1px);
}
.btn-primary-custom:active { transform: translateY(0); }
.btn-primary-custom:disabled {
  background: var(--bg-hover); color: var(--text-dim);
  box-shadow: none; cursor: not-allowed; transform: none;
}

.btn-danger-custom {
  padding: 7px 14px;
  background: rgba(248,113,113,0.12);
  color: var(--red); font-weight: 500; font-size: 0.82rem;
  border: 1px solid rgba(248,113,113,0.25); border-radius: var(--radius-sm);
  cursor: pointer; transition: var(--transition);
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-danger-custom:hover { background: rgba(248,113,113,0.22); border-color: rgba(248,113,113,0.5); }

.btn-secondary-custom {
  padding: 8px 18px;
  background: var(--bg-hover);
  color: var(--text-bright); font-weight: 500; font-size: 0.85rem;
  border: 1px solid var(--border-bright); border-radius: var(--radius-sm);
  cursor: pointer; transition: var(--transition);
  display: inline-flex; align-items: center; gap: 7px;
}
.btn-secondary-custom:hover { background: var(--bg-card-2); border-color: var(--teal); color: var(--teal); }

/* TABLES */
.table-wrap {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.data-table thead th {
  background: var(--bg-void);
  color: var(--text-dim);
  font-weight: 600; font-size: 0.75rem;
  text-transform: uppercase; letter-spacing: 0.08em;
  padding: 13px 18px;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
.data-table tbody td {
  padding: 14px 18px;
  color: var(--text-bright);
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tbody tr { transition: var(--transition); }
.data-table tbody tr:hover td { background: var(--bg-hover); }
.mono { font-family: var(--font-mono); font-size: 0.82rem; color: var(--teal); }

/* FORM CARD */
.form-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px;
  box-shadow: var(--shadow-card);
}
.form-card h4 {
  font-family: var(--font-display);
  font-size: 1.15rem; font-weight: 600;
  color: var(--text-bright); margin-bottom: 20px;
}
.form-label-custom {
  display: block; font-size: 0.78rem; font-weight: 600;
  color: var(--text-dim); letter-spacing: 0.07em;
  text-transform: uppercase; margin-bottom: 6px;
}
.form-control-custom {
  width: 100%;
  background: var(--bg-deep);
  border: 1px solid var(--border-bright);
  color: var(--text-bright);
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  font-family: var(--font-body); font-size: 0.875rem;
  transition: var(--transition);
  outline: none;
  appearance: none;
}
.form-control-custom:focus {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px var(--gold-glow);
  background: var(--bg-card);
}
.form-control-custom::placeholder { color: var(--text-dim); }

/* STATS ROW */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 28px; }
.stat-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px 22px;
  display: flex; align-items: center; gap: 14px;
  transition: var(--transition);
}
.stat-card:hover { border-color: var(--border-bright); background: var(--bg-card-2); }
.stat-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem; flex-shrink: 0;
}
.stat-icon.gold   { background: rgba(240,180,41,0.12);  color: var(--gold);  }
.stat-icon.teal   { background: rgba(56,189,248,0.10);  color: var(--teal);  }
.stat-icon.green  { background: rgba(52,211,153,0.10);  color: var(--green); }
.stat-icon.orange { background: rgba(251,146,60,0.10);  color: var(--orange);}
.stat-label { font-size: 0.76rem; color: var(--text-dim); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
.stat-value { font-family: var(--font-display); font-size: 1.7rem; font-weight: 700; color: var(--text-bright); line-height: 1.1; }

/* TOAST */
#toast-container {
  position: fixed; bottom: 28px; right: 28px; z-index: 9999;
  display: flex; flex-direction: column; gap: 10px;
  pointer-events: none;
}
.toast-item {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 18px;
  background: var(--bg-card-2);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-bright);
  box-shadow: 0 8px 32px rgba(0,0,0,0.5);
  max-width: 360px;
  pointer-events: all;
  animation: toastIn 0.3s ease forwards;
  font-size: 0.875rem;
}
.toast-item.success { border-left: 4px solid var(--green); }
.toast-item.error   { border-left: 4px solid var(--red);   }
.toast-item.warning { border-left: 4px solid var(--orange); }
.toast-item.info    { border-left: 4px solid var(--teal);  }
.toast-icon { font-size: 1.1rem; flex-shrink: 0; }
.toast-item.success .toast-icon { color: var(--green); }
.toast-item.error   .toast-icon { color: var(--red);   }
.toast-item.warning .toast-icon { color: var(--orange);}
.toast-item.info    .toast-icon { color: var(--teal);  }
@keyframes toastIn {
  from { opacity: 0; transform: translateX(40px); }
  to   { opacity: 1; transform: translateX(0); }
}
@keyframes toastOut {
  from { opacity: 1; transform: translateX(0); }
  to   { opacity: 0; transform: translateX(40px); }
}

/* LOADER */
.spinner {
  width: 18px; height: 18px;
  border: 2px solid rgba(255,255,255,0.15);
  border-top-color: var(--gold);
  border-radius: 50%;
  animation: spin 0.7s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

.loading-overlay {
  display: flex; align-items: center; justify-content: center;
  gap: 12px; padding: 48px 0; color: var(--text-dim);
}

/* EMPTY STATE */
.empty-state {
  text-align: center; padding: 60px 20px;
  color: var(--text-dim);
}
.empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }
.empty-state p { margin: 0; font-size: 0.9rem; }

/* DAY PILL */
.day-pill {
  display: inline-block;
  padding: 2px 10px; border-radius: 12px;
  font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04em;
  background: rgba(56,189,248,0.1); color: var(--teal);
  border: 1px solid rgba(56,189,248,0.2);
}

/* CGPA BADGE */
.cgpa-badge {
  font-family: var(--font-mono); font-size: 0.8rem;
  color: var(--gold); background: var(--gold-glow);
  padding: 2px 8px; border-radius: 4px;
  border: 1px solid rgba(240,180,41,0.25);
}

/* DIVIDER */
.section-divider {
  height: 1px; background: var(--border); margin: 28px 0;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .topbar { padding: 12px 16px; flex-wrap: wrap; gap: 10px; }
  .topbar-nav { display: none; }
  .main-content { padding: 16px; }
  .card-grid { grid-template-columns: 1fr; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>
<div class="app-wrapper">

<!-- ═══════════════════════════════════════
     TOPBAR
════════════════════════════════════════ -->
<nav class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
    <span class="brand-name">Uni<span>Reg</span></span>
  </div>

  <div class="topbar-nav">
    <button class="nav-tab active" onclick="switchPanel('dashboard')" id="tab-dashboard">
      <i class="bi bi-grid-1x2"></i> Dashboard
    </button>
    <button class="nav-tab" onclick="switchPanel('courses')" id="tab-courses">
      <i class="bi bi-book"></i> Browse Courses
    </button>
    <button class="nav-tab" onclick="switchPanel('my-courses')" id="tab-my-courses">
      <i class="bi bi-journal-check"></i> My Courses
    </button>
    <button class="nav-tab" onclick="switchPanel('admin')" id="tab-admin">
      <i class="bi bi-shield-lock"></i> Admin
    </button>
  </div>

  <div class="topbar-right">
    <select class="student-selector" id="studentSelect" onchange="onStudentChange()">
      <option value="">— Select student —</option>
    </select>
  </div>
</nav>

<!-- ═══════════════════════════════════════
     MAIN
════════════════════════════════════════ -->
<main class="main-content">

  <!-- ── DASHBOARD ────────────────────── -->
  <section class="panel active" id="panel-dashboard">
    <div class="section-header">
      <h2>Dashboard</h2>
      <p>Overview of the registration system and your academic schedule.</p>
    </div>

    <div class="stats-row" id="statsRow">
      <div class="stat-card">
        <div class="stat-icon gold"><i class="bi bi-people-fill"></i></div>
        <div>
          <div class="stat-label">Students</div>
          <div class="stat-value" id="stat-students">—</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal"><i class="bi bi-book-fill"></i></div>
        <div>
          <div class="stat-label">Courses</div>
          <div class="stat-value" id="stat-courses">—</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
        <div>
          <div class="stat-label">Enrolled</div>
          <div class="stat-value" id="stat-enrolled">—</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="stat-label">Waitlisted</div>
          <div class="stat-value" id="stat-waitlisted">—</div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="table-wrap">
          <div style="padding:18px 20px 14px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-family:var(--font-display); font-weight:600; font-size:0.95rem;">Course Capacity Overview</span>
            <button class="btn-secondary-custom" onclick="loadDashboard()" style="padding:6px 12px; font-size:0.78rem;">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
          </div>
          <div id="dashboardTable">
            <div class="loading-overlay"><div class="spinner"></div> Loading…</div>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="table-wrap">
          <div style="padding:18px 20px 14px; border-bottom:1px solid var(--border);">
            <span style="font-family:var(--font-display); font-weight:600; font-size:0.95rem;">Registered Students</span>
          </div>
          <div id="studentsList">
            <div class="loading-overlay"><div class="spinner"></div> Loading…</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── BROWSE COURSES ───────────────── -->
  <section class="panel" id="panel-courses">
    <div class="section-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
      <div>
        <h2>Browse Courses</h2>
        <p>Select a student and enroll in available courses.</p>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <input type="text" class="form-control-custom" id="courseSearch" placeholder="Search courses…"
               oninput="filterCourses()" style="width:220px; padding:9px 14px;">
        <select class="form-control-custom" id="dayFilter" onchange="filterCourses()" style="width:150px;">
          <option value="">All Days</option>
          <option>SUNDAY</option><option>MONDAY</option><option>TUESDAY</option>
          <option>WEDNESDAY</option><option>THURSDAY</option><option>FRIDAY</option><option>SATURDAY</option>
        </select>
      </div>
    </div>
    <div class="card-grid" id="courseGrid">
      <div class="loading-overlay" style="grid-column:1/-1"><div class="spinner"></div> Loading courses…</div>
    </div>
  </section>

  <!-- ── MY COURSES ───────────────────── -->
  <section class="panel" id="panel-my-courses">
    <div class="section-header">
      <h2>My Courses</h2>
      <p>Your enrolled and waitlisted courses. Drop a course to trigger automatic waitlist promotion.</p>
    </div>
    <div id="myCoursesContent">
      <div class="empty-state">
        <i class="bi bi-person-circle"></i>
        <p>Select a student from the top bar to view their courses.</p>
      </div>
    </div>
  </section>

  <!-- ── ADMIN ────────────────────────── -->
  <section class="panel" id="panel-admin">
    <div class="section-header">
      <h2>Admin Panel</h2>
      <p>Manage students, courses, and view all enrollment records.</p>
    </div>

    <div class="row g-4">
      <!-- Add Student -->
      <div class="col-md-6">
        <div class="form-card">
          <h4><i class="bi bi-person-plus" style="color:var(--gold)"></i> Add New Student</h4>
          <div style="display:flex; flex-direction:column; gap:14px;">
            <div>
              <label class="form-label-custom">Full Name</label>
              <input type="text" class="form-control-custom" id="newStudentName" placeholder="e.g. Rahim Chowdhury">
            </div>
            <div>
              <label class="form-label-custom">Email Address</label>
              <input type="email" class="form-control-custom" id="newStudentEmail" placeholder="student@university.edu">
            </div>
            <div>
              <label class="form-label-custom">CGPA <span style="color:var(--text-dim); text-transform:none; font-size:0.7rem;">(0.00 – 4.00)</span></label>
              <input type="number" class="form-control-custom" id="newStudentCgpa" placeholder="3.75" step="0.01" min="0" max="4">
            </div>
            <button class="btn-primary-custom" onclick="addStudent()">
              <i class="bi bi-plus-circle"></i> Register Student
            </button>
          </div>
        </div>
      </div>

      <!-- Add Course -->
      <div class="col-md-6">
        <div class="form-card">
          <h4><i class="bi bi-journal-plus" style="color:var(--teal)"></i> Add New Course</h4>
          <div style="display:flex; flex-direction:column; gap:14px;">
            <div>
              <label class="form-label-custom">Course Name</label>
              <input type="text" class="form-control-custom" id="newCourseName" placeholder="e.g. Machine Learning">
            </div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label-custom">Max Seats</label>
                <input type="number" class="form-control-custom" id="newCourseSeats" placeholder="30" min="1">
              </div>
              <div class="col-6">
                <label class="form-label-custom">Day</label>
                <select class="form-control-custom" id="newCourseDay">
                  <option>SUNDAY</option><option>MONDAY</option><option>TUESDAY</option>
                  <option>WEDNESDAY</option><option>THURSDAY</option><option>FRIDAY</option><option>SATURDAY</option>
                </select>
              </div>
            </div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label-custom">Start (HHMM)</label>
                <input type="number" class="form-control-custom" id="newCourseStart" placeholder="0900">
              </div>
              <div class="col-6">
                <label class="form-label-custom">End (HHMM)</label>
                <input type="number" class="form-control-custom" id="newCourseEnd" placeholder="1030">
              </div>
            </div>
            <button class="btn-primary-custom" onclick="addCourse()" style="background:linear-gradient(135deg,var(--teal-dim),#0369a1);">
              <i class="bi bi-plus-circle"></i> Add Course
            </button>
          </div>
        </div>
      </div>

      <!-- All Enrollments Table -->
      <div class="col-12">
        <div class="table-wrap">
          <div style="padding:18px 20px 14px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-family:var(--font-display); font-weight:600; font-size:0.95rem;">
              <i class="bi bi-table" style="color:var(--teal)"></i> All Enrollments
            </span>
            <button class="btn-secondary-custom" onclick="loadAllEnrollments()" style="padding:6px 12px; font-size:0.78rem;">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
          </div>
          <div style="overflow-x:auto;" id="allEnrollmentsTable">
            <div class="loading-overlay"><div class="spinner"></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>
</div>

<!-- TOAST CONTAINER -->
<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ═══════════════════════════════════════════════════════════
   CONFIG
═══════════════════════════════════════════════════════════ */
const API = 'api.php';   // Change path if needed

/* ═══════════════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════════════ */
let allCourses   = [];
let allStudents  = [];
let currentStudentId = null;
let myEnrollments = [];

/* ═══════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
  await Promise.all([loadStudents(), loadCourses()]);
  loadDashboard();
});

/* ═══════════════════════════════════════════════════════════
   NAVIGATION
═══════════════════════════════════════════════════════════ */
function switchPanel(name) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-' + name).classList.add('active');
  document.getElementById('tab-' + name).classList.add('active');

  if (name === 'my-courses') renderMyCourses();
  if (name === 'admin')      loadAllEnrollments();
}

/* ═══════════════════════════════════════════════════════════
   API HELPERS
═══════════════════════════════════════════════════════════ */
async function apiFetch(action, method = 'GET', body = null, params = {}) {
  const url = new URL(API, location.href);
  url.searchParams.set('action', action);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
  const opts = { method, headers: {} };
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const res  = await fetch(url, opts);
  const json = await res.json();
  return json;
}

/* ═══════════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════════ */
function toast(msg, type = 'info') {
  const icons = { success:'check-circle-fill', error:'x-circle-fill', warning:'exclamation-triangle-fill', info:'info-circle-fill' };
  const el = document.createElement('div');
  el.className = `toast-item ${type}`;
  el.innerHTML = `<i class="bi bi-${icons[type]} toast-icon"></i><span>${msg}</span>`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => {
    el.style.animation = 'toastOut 0.3s ease forwards';
    setTimeout(() => el.remove(), 300);
  }, 4000);
}

/* ═══════════════════════════════════════════════════════════
   STUDENTS
═══════════════════════════════════════════════════════════ */
async function loadStudents() {
  const r = await apiFetch('get_students');
  if (!r.success) return;
  allStudents = r.data;

  const sel = document.getElementById('studentSelect');
  const cur = sel.value;
  sel.innerHTML = '<option value="">— Select student —</option>';
  allStudents.forEach(s => {
    const o = document.createElement('option');
    o.value = s.student_id;
    o.textContent = `${s.name} (CGPA: ${parseFloat(s.cgpa).toFixed(2)})`;
    sel.appendChild(o);
  });
  if (cur) sel.value = cur;

  document.getElementById('stat-students').textContent = allStudents.length;
  renderStudentsList();
}

function renderStudentsList() {
  const el = document.getElementById('studentsList');
  if (!allStudents.length) { el.innerHTML = '<div class="empty-state"><i class="bi bi-people"></i><p>No students found.</p></div>'; return; }
  el.innerHTML = `<table class="data-table">
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>CGPA</th></tr></thead>
    <tbody>
    ${allStudents.map(s => `
      <tr>
        <td class="mono">${s.student_id}</td>
        <td>${s.name}</td>
        <td style="color:var(--text-mid); font-size:0.82rem;">${s.email}</td>
        <td><span class="cgpa-badge">${parseFloat(s.cgpa).toFixed(2)}</span></td>
      </tr>`).join('')}
    </tbody></table>`;
}

function onStudentChange() {
  currentStudentId = parseInt(document.getElementById('studentSelect').value) || null;
  if (document.getElementById('panel-my-courses').classList.contains('active')) renderMyCourses();
  renderCourseGrid();
}

async function addStudent() {
  const name  = document.getElementById('newStudentName').value.trim();
  const email = document.getElementById('newStudentEmail').value.trim();
  const cgpa  = parseFloat(document.getElementById('newStudentCgpa').value);
  if (!name || !email || isNaN(cgpa)) { toast('Fill all student fields.', 'warning'); return; }

  const r = await apiFetch('add_student', 'POST', { name, email, cgpa });
  if (r.success) {
    toast(`${name} registered (ID: ${r.student_id})`, 'success');
    ['newStudentName','newStudentEmail','newStudentCgpa'].forEach(id => document.getElementById(id).value = '');
    await loadStudents();
  } else {
    toast(r.message, 'error');
  }
}

/* ═══════════════════════════════════════════════════════════
   COURSES
═══════════════════════════════════════════════════════════ */
async function loadCourses() {
  const r = await apiFetch('get_courses');
  if (!r.success) return;
  allCourses = r.data;
  document.getElementById('stat-courses').textContent = allCourses.length;
  renderCourseGrid();
}

function filterCourses() { renderCourseGrid(); }

function renderCourseGrid() {
  const grid   = document.getElementById('courseGrid');
  const search = document.getElementById('courseSearch')?.value.toLowerCase() || '';
  const day    = document.getElementById('dayFilter')?.value || '';

  let courses = allCourses.filter(c => {
    const matchSearch = !search || c.course_name.toLowerCase().includes(search);
    const matchDay    = !day || c.day === day;
    return matchSearch && matchDay;
  });

  if (!courses.length) {
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><i class="bi bi-search"></i><p>No courses match your filter.</p></div>';
    return;
  }

  // Build a set of enrolled/waitlisted course ids for the current student
  const enrolledIds    = new Set(myEnrollments.filter(e => e.status === 'ENROLLED').map(e => parseInt(e.course_id)));
  const waitlistedIds  = new Set(myEnrollments.filter(e => e.status === 'WAITLISTED').map(e => parseInt(e.course_id)));

  grid.innerHTML = courses.map(c => {
    const seats    = parseInt(c.seats_available);
    const max      = parseInt(c.max_seats);
    const enrolled = parseInt(c.enrolled_count);
    const pct      = Math.round((enrolled / max) * 100);
    const isFull   = seats <= 0;
    const isLow    = seats > 0 && seats <= 2;
    const isEnrolled   = enrolledIds.has(parseInt(c.course_id));
    const isWaitlisted = waitlistedIds.has(parseInt(c.course_id));
    const barClass     = isFull ? 'full' : isLow ? 'low' : 'partial';

    let statusBadge = isFull
      ? '<span class="badge-status badge-full"><i class="bi bi-x-circle"></i> Full</span>'
      : `<span class="badge-status badge-available"><i class="bi bi-check-circle"></i> ${seats} seat${seats !== 1 ? 's' : ''} left</span>`;
    if (isEnrolled)   statusBadge = '<span class="badge-status badge-enrolled"><i class="bi bi-check2-circle"></i> Enrolled</span>';
    if (isWaitlisted) statusBadge = '<span class="badge-status badge-waitlisted"><i class="bi bi-hourglass"></i> Waitlisted</span>';

    let btnHtml;
    if (isEnrolled || isWaitlisted) {
      btnHtml = `<button class="btn-primary-custom" disabled style="opacity:0.5">
        <i class="bi bi-check2"></i> ${isEnrolled ? 'Already Enrolled' : 'On Waitlist'}
      </button>`;
    } else if (!currentStudentId) {
      btnHtml = `<button class="btn-primary-custom" disabled style="opacity:0.4">
        <i class="bi bi-person-check"></i> Select Student First
      </button>`;
    } else {
      const label = isFull ? 'Join Waitlist' : 'Enroll Now';
      const icon  = isFull ? 'bi-hourglass-split' : 'bi-plus-circle';
      const style = isFull ? 'background:linear-gradient(135deg,#ea580c,#c2410c)' : '';
      btnHtml = `<button class="btn-primary-custom" style="${style}"
        onclick="enroll(${c.course_id})">
        <i class="bi ${icon}"></i> ${label}
      </button>`;
    }

    return `<div class="course-card">
      <div class="course-card-name">${c.course_name}</div>
      <div class="course-meta">
        <div class="meta-row"><i class="bi bi-calendar3"></i><span class="day-pill">${c.day}</span></div>
        <div class="meta-row"><i class="bi bi-clock"></i>${c.start_time_fmt} – ${c.end_time_fmt}</div>
        <div class="meta-row"><i class="bi bi-people"></i>${enrolled} / ${max} enrolled</div>
        ${parseInt(c.waitlisted_count) > 0
          ? `<div class="meta-row"><i class="bi bi-list-ol"></i>${c.waitlisted_count} on waitlist</div>` : ''}
      </div>
      <div class="seat-bar">
        <div class="seat-bar-fill ${barClass}" style="width:${pct}%"></div>
      </div>
      <div class="seat-info">
        <span>${statusBadge}</span>
        <span>ID #${c.course_id}</span>
      </div>
      ${btnHtml}
    </div>`;
  }).join('');
}

async function addCourse() {
  const course_name = document.getElementById('newCourseName').value.trim();
  const max_seats   = parseInt(document.getElementById('newCourseSeats').value);
  const day         = document.getElementById('newCourseDay').value;
  const start_time  = parseInt(document.getElementById('newCourseStart').value);
  const end_time    = parseInt(document.getElementById('newCourseEnd').value);

  if (!course_name || !max_seats || !start_time || !end_time) { toast('Fill all course fields.', 'warning'); return; }
  if (end_time <= start_time) { toast('End time must be after start time.', 'warning'); return; }

  const r = await apiFetch('add_course', 'POST', { course_name, max_seats, day, start_time, end_time });
  if (r.success) {
    toast(`Course added (ID: ${r.course_id})`, 'success');
    ['newCourseName','newCourseSeats','newCourseStart','newCourseEnd'].forEach(id => document.getElementById(id).value = '');
    await loadCourses();
  } else {
    toast(r.message, 'error');
  }
}

/* ═══════════════════════════════════════════════════════════
   ENROLLMENTS
═══════════════════════════════════════════════════════════ */
async function enroll(courseId) {
  if (!currentStudentId) { toast('Please select a student first.', 'warning'); return; }

  const r = await apiFetch('enroll', 'POST', { student_id: currentStudentId, course_id: courseId });
  if (r.success) {
    const type = r.status === 'ENROLLED' ? 'success' : 'warning';
    toast(r.message, type);
    await refreshAll();
  } else {
    toast(r.message, 'error');
  }
}

async function drop(courseId) {
  if (!currentStudentId || !confirm('Drop this course?')) return;
  const r = await apiFetch('drop', 'POST', { student_id: currentStudentId, course_id: courseId });
  if (r.success) {
    toast(r.message, 'success');
    await refreshAll();
  } else {
    toast(r.message, 'error');
  }
}

async function loadMyEnrollments() {
  if (!currentStudentId) { myEnrollments = []; return; }
  const r = await apiFetch('get_enrollments', 'GET', null, { student_id: currentStudentId });
  myEnrollments = r.success ? r.data : [];
  document.getElementById('stat-enrolled').textContent   = myEnrollments.filter(e => e.status === 'ENROLLED').length;
  document.getElementById('stat-waitlisted').textContent = myEnrollments.filter(e => e.status === 'WAITLISTED').length;
}

async function renderMyCourses() {
  const el = document.getElementById('myCoursesContent');
  if (!currentStudentId) {
    el.innerHTML = '<div class="empty-state"><i class="bi bi-person-circle"></i><p>Select a student from the top bar.</p></div>';
    return;
  }
  el.innerHTML = '<div class="loading-overlay"><div class="spinner"></div> Loading…</div>';
  await loadMyEnrollments();

  const student = allStudents.find(s => parseInt(s.student_id) === currentStudentId);
  const enrolled   = myEnrollments.filter(e => e.status === 'ENROLLED');
  const waitlisted = myEnrollments.filter(e => e.status === 'WAITLISTED');

  let html = '';
  if (student) {
    html += `<div style="display:flex; align-items:center; gap:14px; margin-bottom:24px; padding:18px 22px;
                         background:var(--bg-card); border-radius:var(--radius-md); border:1px solid var(--border);">
      <div style="width:48px; height:48px; border-radius:50%; background:var(--gold-glow); border:2px solid var(--gold);
                  display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:var(--gold);">
        <i class="bi bi-person-fill"></i>
      </div>
      <div>
        <div style="font-family:var(--font-display); font-size:1.1rem; font-weight:600;">${student.name}</div>
        <div style="color:var(--text-mid); font-size:0.82rem;">${student.email} &nbsp;·&nbsp; CGPA: <span class="cgpa-badge">${parseFloat(student.cgpa).toFixed(2)}</span></div>
      </div>
    </div>`;
  }

  if (!myEnrollments.length) {
    html += '<div class="empty-state"><i class="bi bi-journal-x"></i><p>No enrollments yet. Browse courses to enroll.</p></div>';
  } else {
    if (enrolled.length) {
      html += `<h6 style="color:var(--green); font-size:0.78rem; font-weight:700; letter-spacing:0.08em;
                           text-transform:uppercase; margin-bottom:12px;">
        <i class="bi bi-check2-circle"></i> Enrolled Courses (${enrolled.length})
      </h6>`;
      html += renderEnrollmentTable(enrolled, true);
    }
    if (waitlisted.length) {
      html += `<div style="margin-top:24px;"></div>
      <h6 style="color:var(--orange); font-size:0.78rem; font-weight:700; letter-spacing:0.08em;
                  text-transform:uppercase; margin-bottom:12px;">
        <i class="bi bi-hourglass-split"></i> Waitlisted Courses (${waitlisted.length})
      </h6>`;
      html += renderEnrollmentTable(waitlisted, true);
    }
  }

  el.innerHTML = html;
}

function renderEnrollmentTable(rows, showDrop = true) {
  return `<div class="table-wrap"><table class="data-table">
    <thead><tr>
      <th>Course</th><th>Day</th><th>Time</th><th>Status</th>${showDrop ? '<th>Action</th>' : ''}
    </tr></thead>
    <tbody>
    ${rows.map(e => `
      <tr>
        <td><strong>${e.course_name}</strong></td>
        <td><span class="day-pill">${e.day}</span></td>
        <td style="color:var(--text-mid); font-size:0.82rem; font-family:var(--font-mono);">${e.start_time_fmt} – ${e.end_time_fmt}</td>
        <td>
          <span class="badge-status ${e.status === 'ENROLLED' ? 'badge-enrolled' : 'badge-waitlisted'}">
            <i class="bi ${e.status === 'ENROLLED' ? 'bi-check2-circle' : 'bi-hourglass'}"></i>
            ${e.status}
          </span>
        </td>
        ${showDrop ? `<td><button class="btn-danger-custom" onclick="drop(${e.course_id})">
          <i class="bi bi-trash3"></i> Drop
        </button></td>` : ''}
      </tr>`).join('')}
    </tbody></table></div>`;
}

async function loadAllEnrollments() {
  const el = document.getElementById('allEnrollmentsTable');
  el.innerHTML = '<div class="loading-overlay"><div class="spinner"></div></div>';
  const r = await apiFetch('get_all_enrollments');
  if (!r.success || !r.data.length) {
    el.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>No enrollments found.</p></div>';
    return;
  }
  el.innerHTML = `<table class="data-table">
    <thead><tr>
      <th>#</th><th>Student</th><th>CGPA</th><th>Course</th><th>Day</th><th>Time</th><th>Status</th>
    </tr></thead>
    <tbody>
    ${r.data.map(e => `
      <tr>
        <td class="mono">${e.enrollment_id}</td>
        <td>${e.student_name}</td>
        <td><span class="cgpa-badge">${parseFloat(e.cgpa).toFixed(2)}</span></td>
        <td>${e.course_name}</td>
        <td><span class="day-pill">${e.day}</span></td>
        <td style="font-family:var(--font-mono); color:var(--text-mid); font-size:0.8rem;">${e.start_time_fmt} – ${e.end_time_fmt}</td>
        <td><span class="badge-status ${e.status === 'ENROLLED' ? 'badge-enrolled' : 'badge-waitlisted'}">
          <i class="bi ${e.status === 'ENROLLED' ? 'bi-check2-circle' : 'bi-hourglass'}"></i>
          ${e.status}
        </span></td>
      </tr>`).join('')}
    </tbody></table>`;
}

/* ═══════════════════════════════════════════════════════════
   DASHBOARD
═══════════════════════════════════════════════════════════ */
async function loadDashboard() {
  await loadCourses();

  const tbl = document.getElementById('dashboardTable');
  if (!allCourses.length) {
    tbl.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>No courses found.</p></div>';
    return;
  }

  let totalEnrolled = 0, totalWaitlisted = 0;
  allCourses.forEach(c => {
    totalEnrolled    += parseInt(c.enrolled_count);
    totalWaitlisted  += parseInt(c.waitlisted_count);
  });
  document.getElementById('stat-enrolled').textContent   = totalEnrolled;
  document.getElementById('stat-waitlisted').textContent = totalWaitlisted;

  tbl.innerHTML = `<table class="data-table">
    <thead><tr>
      <th>Course</th><th>Day</th><th>Capacity</th><th>Enrolled</th><th>Waitlist</th><th>Status</th>
    </tr></thead>
    <tbody>
    ${allCourses.map(c => {
      const pct  = Math.round((parseInt(c.enrolled_count) / parseInt(c.max_seats)) * 100);
      const isFull = parseInt(c.seats_available) <= 0;
      return `<tr>
        <td><strong>${c.course_name}</strong></td>
        <td><span class="day-pill">${c.day}</span></td>
        <td>
          <div style="display:flex; align-items:center; gap:8px; min-width:130px;">
            <div class="seat-bar" style="flex:1; margin-bottom:0;">
              <div class="seat-bar-fill ${pct >= 100 ? 'full' : pct >= 70 ? 'low' : 'partial'}"
                   style="width:${pct}%"></div>
            </div>
            <span style="font-size:0.75rem; color:var(--text-dim); font-family:var(--font-mono);">${pct}%</span>
          </div>
        </td>
        <td class="mono">${c.enrolled_count} / ${c.max_seats}</td>
        <td class="mono" style="color:var(--orange);">${c.waitlisted_count}</td>
        <td>
          ${isFull
            ? '<span class="badge-status badge-full"><i class="bi bi-x-circle"></i> Full</span>'
            : `<span class="badge-status badge-available"><i class="bi bi-check-circle"></i> ${c.seats_available} left</span>`}
        </td>
      </tr>`;
    }).join('')}
    </tbody></table>`;
}

/* ═══════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════ */
async function refreshAll() {
  await loadMyEnrollments();
  await loadCourses();
  renderMyCourses();
}
</script>
</body>
</html>