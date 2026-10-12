// Frame for one dashboard chart: title, a one line takeaway, legend and the chart (design/components/ChartCard).
// The aria-label repeats the takeaway in words, so the highlight is not colour only.
export default function ChartCard({ title, takeaway, legend = [], className = '', children }) {
  return (
    <section className={`wl-card wl-chart-card ${className}`}>
      <div className="wl-chart-card__head">
        <div>
          <h3 className="wl-chart-card__title">{title}</h3>
          <p className="wl-chart-card__sub">{takeaway}</p>
        </div>
        {legend.length > 0 && (
          <div className="wl-chart-card__legend">
            {legend.map((item) => (
              <span key={item.label} style={{ '--_c': `var(--${item.color})` }}>
                <i />
                {item.label}
              </span>
            ))}
          </div>
        )}
      </div>
      <div className="wl-chart-card__body" role="img" aria-label={`${title}. ${takeaway}`}>
        {children}
      </div>
    </section>
  );
}

// Recharts needs real colour values, so we read them from the design tokens once (single source of colour).
export function chartColor(tokenName) {
  return getComputedStyle(document.documentElement).getPropertyValue(`--${tokenName}`).trim();
}
