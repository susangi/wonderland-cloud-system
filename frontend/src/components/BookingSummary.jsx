import { formatDate, formatMoney } from '../utils/format';

// What the visitor is buying (design/components/BookingSummary).
// Values not chosen yet show a dash.
export default function BookingSummary({ tierName, guests, visitDate, slotLabel, price, currency = 'USD', heading = 'Your booking' }) {
  return (
    <div className="wl-card wl-summary-card">
      <div className="wl-stack">
        <h3>{heading}</h3>
        <dl className="wl-summary">
          <dt>Ticket</dt>
          <dd>{tierName || '–'}</dd>
          <dt>Guests</dt>
          <dd>{guests || '–'}</dd>
          <dt>Date</dt>
          <dd>{visitDate ? formatDate(visitDate) : '–'}</dd>
          <dt>Time slot</dt>
          <dd>{slotLabel || '–'}</dd>
          <dt className="is-total">Total</dt>
          <dd className="is-total">{price !== undefined && price !== null ? formatMoney(price, currency) : '–'}</dd>
        </dl>
        <p className="wl-hint">One price for the whole ticket. Pay within 15 minutes to keep your places.</p>
      </div>
    </div>
  );
}
