import { thrillLabel } from '../utils/status';

// 1 to 4 bars plus the word. The bars are decoration; screen readers read the word.
export default function ThrillMeter({ level }) {
  return (
    <span className="wl-thrill" data-level={level}>
      <span className="wl-thrill__bars" aria-hidden="true">
        <i />
        <i />
        <i />
        <i />
      </span>
      <span className="wl-thrill__label">{thrillLabel(level)}</span>
    </span>
  );
}
