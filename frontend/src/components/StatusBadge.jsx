import { Check, CircleAlert, Clock, DoorOpen, X } from 'lucide-react';
import { statusInfo } from '../utils/status';

// Status as icon + word, never colour alone (design/components/StatusBadge).
const ICONS = { check: Check, clock: Clock, alert: CircleAlert, x: X, door: DoorOpen };

export default function StatusBadge({ status }) {
  const { label, className, icon } = statusInfo(status);
  const Icon = ICONS[icon];

  return (
    <span className={`wl-status ${className}`}>
      <Icon aria-hidden="true" />
      {label}
    </span>
  );
}
