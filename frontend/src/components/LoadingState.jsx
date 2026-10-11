// Loading states (design/components/States): a spinner for whole page waits,
// skeleton shapes for lists so the page does not jump when data arrives.

export function LoadingState({ text = 'Loading…' }) {
  return (
    <div className="wl-center" role="status">
      <div className="wl-spinner" aria-hidden="true" />
      <p className="wl-muted">{text}</p>
    </div>
  );
}

export function SkeletonCards({ count = 4, label = 'Loading…', withArt = true }) {
  return (
    <div className="wl-grid wl-grid--4" aria-busy="true">
      {Array.from({ length: count }, (_, index) => (
        <div key={index} className="wl-card wl-attraction" aria-hidden="true">
          {withArt && <div className="wl-skeleton" style={{ height: 120, borderRadius: 0 }} />}
          <div className="wl-attraction__body">
            <div className="wl-skeleton wl-skeleton--title" />
            <div className="wl-skeleton wl-skeleton--text" />
            <div className="wl-skeleton wl-skeleton--text" style={{ width: '80%' }} />
          </div>
        </div>
      ))}
      <span className="wl-sr-only">{label}</span>
    </div>
  );
}

export function SkeletonRows({ count = 5, label = 'Loading…' }) {
  return (
    <div className="wl-card wl-stack" aria-busy="true">
      {Array.from({ length: count }, (_, index) => (
        <div key={index} className="wl-skeleton wl-skeleton--text" aria-hidden="true" />
      ))}
      <span className="wl-sr-only">{label}</span>
    </div>
  );
}
