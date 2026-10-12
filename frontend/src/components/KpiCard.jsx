// Headline number on the monitoring dashboard (design/components/KpiCard).
export default function KpiCard({ label, value, note, isLoading = false }) {
  return (
    <div className="wl-card wl-kpi">
      <span className="wl-overline">{label}</span>
      {isLoading ? (
        <span className="wl-skeleton" style={{ height: 32 }} aria-hidden="true" />
      ) : (
        <span className="wl-kpi__value">{value}</span>
      )}
      {note && !isLoading && <span className="wl-muted">{note}</span>}
    </div>
  );
}
