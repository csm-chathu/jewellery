/**
 * Format any date/ISO string as "01 Feb 2026".
 * Handles date-only strings (YYYY-MM-DD) without timezone drift.
 */
export function fmtDate(d) {
  if (!d) return '—'
  const s = String(d)
  // Date-only (YYYY-MM-DD) — parse in local time to avoid UTC shift
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
    const [y, m, day] = s.split('-').map(Number)
    return new Date(y, m - 1, day).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
  }
  return new Date(s).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

/** Format as "01 Feb 2026 14:30" */
export function fmtDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
