import { init as initDashboard } from './pages/dashboard.js';
import { init as initRoster } from './pages/roster.js';
import { init as initRest } from './pages/rest.js';
import { init as initMonthly } from './pages/monthly.js';
import { init as initReports } from './pages/reports.js';

const PAGE_MODULES = {
  dashboard: { init: initDashboard },
  roster: { init: initRoster },
  rest: { init: initRest },
  monthly: { init: initMonthly },
  reports: { init: initReports },
};

export function getPageModuleMap() {
  return PAGE_MODULES;
}

export function getPageModule(page) {
  return PAGE_MODULES[page] || null;
}

export default PAGE_MODULES;
