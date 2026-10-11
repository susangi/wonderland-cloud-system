// − / value / + for the number of guests (design/components/Controls).
// Limited to the tier's min and max. Fixed tiers (Individual, Couple) show it disabled.
export default function GuestCounter({ value, min, max, onChange }) {
  const isFixed = min === max;

  return (
    <div className="wl-field">
      <span className="wl-label" id="guests-label">Guests</span>
      <div className="wl-counter" role="group" aria-labelledby="guests-label">
        <button type="button" aria-label="Fewer guests" disabled={isFixed || value <= min} onClick={() => onChange(value - 1)}>−</button>
        <output aria-live="polite">{value}</output>
        <button type="button" aria-label="More guests" disabled={isFixed || value >= max} onClick={() => onChange(value + 1)}>+</button>
      </div>
    </div>
  );
}
