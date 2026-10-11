// One place that turns API status values into the words and badge styles visitors see
// (design/components/StatusBadge). Never show raw values like PENDING_PAYMENT on screen.

const STATUSES = {
  CONFIRMED: { label: 'Confirmed', className: 'wl-status--confirmed', icon: 'check' },
  PENDING_PAYMENT: { label: 'Pending payment', className: 'wl-status--pending', icon: 'clock' },
  PAYMENT_FAILED: { label: 'Payment failed', className: 'wl-status--failed', icon: 'alert' },
  EXPIRED: { label: 'Expired', className: 'wl-status--expired', icon: 'x' },
  CHECKED_IN: { label: 'Checked in', className: 'wl-status--checked-in', icon: 'door' },
  active: { label: 'Active', className: 'wl-status--active', icon: 'check' },
  retired: { label: 'Retired', className: 'wl-status--retired', icon: 'x' },
  inactive: { label: 'Inactive', className: 'wl-status--inactive', icon: 'x' },
};

export function statusInfo(status) {
  return STATUSES[status] || { label: status, className: 'wl-status--expired', icon: 'x' };
}

export function statusLabel(status) {
  return statusInfo(status).label;
}

// Labels for the attraction filters and badges.
export const CATEGORIES = [
  { value: 'rollercoaster', label: 'Rollercoaster' },
  { value: 'water', label: 'Water' },
  { value: 'kids', label: 'Kids' },
  { value: 'interactive', label: 'Interactive' },
];

export const THRILL_LEVELS = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'extreme', label: 'Extreme' },
];

export function categoryLabel(category) {
  return CATEGORIES.find((item) => item.value === category)?.label || category;
}

export function thrillLabel(thrillLevel) {
  return THRILL_LEVELS.find((item) => item.value === thrillLevel)?.label || thrillLevel;
}
