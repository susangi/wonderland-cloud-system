import TierCard, { bestValueTierId } from '../../components/TierCard';
import GuestCounter from '../../components/GuestCounter';
import Alert from '../../components/Alert';
import { SkeletonCards } from '../../components/LoadingState';
import StepFooter from './StepFooter';

// Step 1: one tier per booking (FR-V-07) and the number of guests inside its limits (FR-V-08).
export default function TicketStep({ tiers, isLoading, error, onRetry, selectedTier, guests, onSelectTier, onGuestsChange, onContinue, shortSummary }) {
  const bestValueId = bestValueTierId(tiers);

  return (
    <>
      <h2>Choose your ticket</h2>

      {isLoading && <SkeletonCards count={4} withArt={false} label="Loading ticket tiers…" />}
      {error && (
        <Alert variant="warning" title="We can't load prices right now" actions={<button type="button" className="wl-btn wl-btn--sm" onClick={onRetry}>Try again</button>}>
          Please try again shortly.
        </Alert>
      )}

      {!isLoading && !error && (
        <div className="wl-tier-grid" role="radiogroup" aria-label="Ticket tier">
          {tiers.map((tier) => (
            <TierCard
              key={tier.id}
              tier={tier}
              isBestValue={tier.id === bestValueId}
              radioName="tier"
              checked={selectedTier?.id === tier.id}
              onSelect={onSelectTier}
            />
          ))}
        </div>
      )}

      {selectedTier && (
        <div className="wl-stack" style={{ gap: 'var(--space-2)' }}>
          <GuestCounter value={guests} min={selectedTier.min_guests} max={selectedTier.max_guests} onChange={onGuestsChange} />
          <p className="wl-hint">
            {selectedTier.min_guests === selectedTier.max_guests
              ? `${selectedTier.name} tickets are for exactly ${selectedTier.max_guests} ${selectedTier.max_guests === 1 ? 'guest' : 'guests'}.`
              : `${selectedTier.name} tickets cover up to ${selectedTier.max_guests} guests.`}
          </p>
        </div>
      )}

      <StepFooter onContinue={onContinue} canContinue={selectedTier !== null} shortSummary={shortSummary} />
    </>
  );
}
