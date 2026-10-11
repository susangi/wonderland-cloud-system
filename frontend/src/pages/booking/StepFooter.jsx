// Back / Continue row under each booking step. On phones it sticks to the bottom
// and also shows the short summary ("USD 140 · Family, 4 guests").
export default function StepFooter({ onBack, onContinue, canContinue, continueLabel = 'Continue', shortSummary, isBusy = false }) {
  return (
    <div className="wl-step-footer">
      {onBack ? <button type="button" className="wl-btn" onClick={onBack}>Back</button> : <span />}
      <span className="wl-step-footer__summary">{shortSummary}</span>
      <button
        type="button"
        className={`wl-btn wl-btn--primary${isBusy ? ' is-loading' : ''}`}
        aria-busy={isBusy}
        disabled={!canContinue}
        onClick={onContinue}
      >
        {continueLabel}
      </button>
    </div>
  );
}
