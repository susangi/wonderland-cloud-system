import { formatMoney } from '../utils/format';

// Ticket tier card (design/components/TierCard).
// As a radio card in booking step 1 (pass name + checked + onSelect), or as a plain card on Home.

export function guestsText(tier) {
  if (tier.min_guests === tier.max_guests) {
    return `${tier.max_guests} ${tier.max_guests === 1 ? 'guest' : 'guests'}`;
  }
  return `Up to ${tier.max_guests} guests`;
}

// The price is for the whole ticket. Per guest = price / max guests.
export function perGuestPrice(tier) {
  return tier.price / tier.max_guests;
}

// "Best value" goes only to the tier with the lowest price per guest.
export function bestValueTierId(tiers) {
  if (tiers.length === 0) return null;

  return tiers.reduce((best, tier) => (perGuestPrice(tier) < perGuestPrice(best) ? tier : best)).id;
}

export default function TierCard({ tier, isBestValue, radioName, checked, onSelect }) {
  const content = (
    <>
      {isBestValue && <span className="wl-tier__tag wl-badge wl-badge--highlight">Best value</span>}
      <span className="wl-tier__name">{tier.name}</span>
      <span className="wl-tier__guests">{guestsText(tier)}</span>
      <span className="wl-tier__price">
        <small>{tier.currency}</small>
        {Number(tier.price).toLocaleString('en-US')}
      </span>
      <span className="wl-tier__per">{formatMoney(perGuestPrice(tier), tier.currency)} per guest</span>
    </>
  );

  if (!radioName) {
    return <div className="wl-tier">{content}</div>;
  }

  return (
    <label className={`wl-tier${checked ? ' is-selected' : ''}`}>
      <input type="radio" name={radioName} checked={checked} onChange={() => onSelect(tier)} />
      <span className="wl-tier__check" aria-hidden="true" />
      {content}
    </label>
  );
}
