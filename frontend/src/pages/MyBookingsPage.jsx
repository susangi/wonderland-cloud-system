import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Ticket } from 'lucide-react';
import StatusBadge from '../components/StatusBadge';
import EmptyState from '../components/EmptyState';
import { ErrorAlert } from '../components/Alert';
import { SkeletonRows } from '../components/LoadingState';
import { getMyBookings } from '../api/bookingApi';
import { getMyVisits } from '../api/eventApi';
import { formatMoney, formatShortDate, slotName } from '../utils/format';
import { PAYMENT_HOLD_MINUTES } from '../utils/config';

// Booking history, newest first (design/Screens.md section 6, FR-V-13).
export default function MyBookingsPage() {
  const [bookings, setBookings] = useState([]);
  const [checkedInReferences, setCheckedInReferences] = useState(new Set());
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  function loadBookings() {
    setIsLoading(true);
    setError(null);
    getMyBookings()
      .then(setBookings)
      .catch(setError)
      .finally(() => setIsLoading(false));

    // booking-service does not know about check-ins. A reference in my visits means "Checked in" (design 13.6).
    // If event-service is down we simply do not show the badge.
    getMyVisits()
      .then((visits) => setCheckedInReferences(new Set(visits.filter((visit) => visit.event_type === 'GATE_CHECKIN').map((visit) => visit.booking_reference))))
      .catch(() => setCheckedInReferences(new Set()));
  }

  useEffect(loadBookings, []);

  return (
    <div className="wl-container wl-stack">
      <div className="wl-page-head">
        <h1>My bookings</h1>
        <Link className="wl-btn wl-btn--cta" to="/book">Book tickets</Link>
      </div>

      {isLoading && <SkeletonRows count={5} label="Loading your bookings…" />}
      {error && <ErrorAlert error={error} onRetry={loadBookings} />}
      {!isLoading && !error && bookings.length === 0 && (
        <EmptyState icon={Ticket} title="No bookings yet" text="Book your first visit to see it here." action={<Link className="wl-btn wl-btn--cta" to="/book">Book tickets</Link>} />
      )}
      {!isLoading && !error && bookings.length > 0 && (
        <div className="wl-table-wrap">
          <table className="wl-table wl-table--stack">
            <thead>
              <tr>
                <th>Reference</th><th>Visit date</th><th>Slot</th><th>Ticket</th><th className="is-num">Guests</th>
                <th className="is-num">Total</th><th>Status</th><th><span className="wl-sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              {bookings.map((booking) => (
                <BookingRow key={booking.booking_reference} booking={booking} isCheckedIn={checkedInReferences.has(booking.booking_reference)} />
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

function BookingRow({ booking, isCheckedIn }) {
  const canPay = booking.status === 'PENDING_PAYMENT' || booking.status === 'PAYMENT_FAILED';

  return (
    <tr>
      <td data-label="Reference" className="wl-ref">{booking.booking_reference}</td>
      <td data-label="Visit date">{formatShortDate(booking.visit_date)}</td>
      <td data-label="Slot">{slotName(booking.time_slot_label)}</td>
      <td data-label="Ticket">{booking.tier_name}</td>
      <td data-label="Guests" className="is-num">{booking.guests}</td>
      <td data-label="Total" className="is-num">{formatMoney(booking.price_paid, booking.currency)}</td>
      <td data-label="Status">
        <div className="wl-stack" style={{ gap: 'var(--space-1)' }}>
          <StatusBadge status={isCheckedIn ? 'CHECKED_IN' : booking.status} />
          {canPay && <HoldTimeLeft createdAt={booking.created_at} />}
        </div>
      </td>
      <td className="wl-actions">
        {booking.status === 'CONFIRMED' && <Link className="wl-btn wl-btn--sm" to={`/tickets/${booking.booking_reference}`}>View ticket</Link>}
        {canPay && <Link className="wl-btn wl-btn--sm wl-btn--cta" to={`/book?ref=${booking.booking_reference}`}>Pay now</Link>}
      </td>
    </tr>
  );
}

// "Pay within 9 min" under pending rows.
function HoldTimeLeft({ createdAt }) {
  const minutesLeft = Math.ceil((new Date(createdAt).getTime() + PAYMENT_HOLD_MINUTES * 60000 - Date.now()) / 60000);

  if (minutesLeft <= 0) {
    return <span className="wl-hint">Hold time is over</span>;
  }

  return <span className="wl-hint">Pay within {minutesLeft} min</span>;
}
